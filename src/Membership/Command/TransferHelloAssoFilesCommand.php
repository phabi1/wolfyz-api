<?php

namespace App\Membership\Command;

use App\Core\Command\CommandInterface;
use App\Core\Command\Input;
use App\Core\Command\Output\Color;
use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Membership\HelloAsso\FileTransfer;
use App\Membership\HelloAsso\TransferAbortedException;

/**
 * Copies the files of the subscriptions still stored by HelloAsso (links
 * set by the sync of the HelloAsso export) to the files service, and points
 * the subscriptions to the copies.
 *
 *   bin/console membership:transfer-helloasso-files [--campaign=<id>] [--dry-run] [--delay=<seconds>]
 *
 * Safe to run again: only HelloAsso links are transferred. Stops at the
 * first error every other file would hit too (rate limit, access refused),
 * to stay under the rate limits of HelloAsso.
 */
class TransferHelloAssoFilesCommand implements CommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const FIELDS = ['medical_certificate', 'identity_photo', 'medical_form'];
    private const PAGE = 200;

    public function execute(Input $input): int
    {
        $dryRun = $input->options->has('dry-run');
        $campaignId = $input->options->get('campaign');
        if ($campaignId !== null && !ctype_digit((string) $campaignId)) {
            echo Color::apply('--campaign takes the id of a campaign', 'red') . PHP_EOL;

            return 1;
        }

        $delay = $input->options->get('delay') ?? '1';
        if (!is_numeric($delay) || (float) $delay < 0) {
            echo Color::apply('--delay takes a number of seconds', 'red') . PHP_EOL;

            return 1;
        }
        $delay = (int) round((float) $delay * 1000000);

        $repository = $this->container->get('entity.manager')->getRepository('wolf-memberships.subscription');
        $transfer = $this->fileTransfer();

        $filters = $campaignId !== null ? ['campaign_id' => ['eq' => (int) $campaignId]] : [];
        $counts = ['transferred' => 0, 'failed' => 0, 'not_a_link' => 0];
        $offset = 0;
        $aborted = null;
        $first = true;

        do {
            $subscriptions = $repository->find($filters, [
                'offset' => $offset,
                'limit' => self::PAGE,
                'sort' => 'id',
                'order' => 'asc',
            ]);
            $offset += self::PAGE;

            foreach ($subscriptions as $subscription) {
                $changes = [];
                foreach (self::FIELDS as $field) {
                    $value = $subscription->{$field} ?? null;
                    if (!is_string($value) || trim($value) === '') {
                        continue;
                    }
                    if (!FileTransfer::isHelloAssoUrl($value)) {
                        // "Voir le document": the sync read the text of the cell, not its link
                        if (stripos($value, 'voir le document') !== false) {
                            $counts['not_a_link']++;
                            echo Color::apply(sprintf('#%d %s: "%s" (no link, sync again from a CSV export)', $subscription->id, $field, $value), 'yellow') . PHP_EOL;
                        }
                        continue;
                    }

                    if ($dryRun) {
                        $counts['transferred']++;
                        echo sprintf('#%d %s: %s (dry run)', $subscription->id, $field, $value) . PHP_EOL;
                        continue;
                    }

                    if (!$first && $delay > 0) {
                        usleep($delay);
                    }
                    $first = false;

                    try {
                        $changes[$field] = $transfer->transfer($value);
                        $counts['transferred']++;
                        echo sprintf('#%d %s: %s', $subscription->id, $field, $changes[$field]) . PHP_EOL;
                    } catch (TransferAbortedException $e) {
                        $counts['failed']++;
                        $aborted = sprintf('#%d %s: %s', $subscription->id, $field, $e->getMessage());
                        break;
                    } catch (\Throwable $e) {
                        $counts['failed']++;
                        echo Color::apply(sprintf('#%d %s: %s', $subscription->id, $field, $e->getMessage()), 'red') . PHP_EOL;
                    }
                }

                // The repository, not the subscription service: the service
                // validates the whole subscription and clears the competition
                // fields of other licenses
                if ($changes !== []) {
                    $repository->update($subscription->id, $changes);
                }
                if ($aborted !== null) {
                    break 2;
                }
            }
        } while (count($subscriptions) === self::PAGE);

        if ($aborted !== null) {
            echo Color::apply('Stopped: ' . $aborted, 'red') . PHP_EOL;
        }

        echo PHP_EOL . sprintf(
            '%d %s, %d failed, %d without link',
            $counts['transferred'],
            $dryRun ? 'to transfer' : 'transferred',
            $counts['failed'],
            $counts['not_a_link'],
        ) . PHP_EOL;

        return $counts['failed'] > 0 ? 1 : 0;
    }

    private function fileTransfer(): FileTransfer
    {
        $helloAsso = $this->container->get('wolf-helloasso.sdk');

        return new FileTransfer(
            new \GuzzleHttp\Client(),
            $this->container->get('file.presigned-url'),
            static fn (): string => $helloAsso->getAccessToken(),
        );
    }

    public static function getName(): string
    {
        return 'membership:transfer-helloasso-files';
    }

    public static function getDescription(): string
    {
        return 'Copies the subscription files stored by HelloAsso to the files service.';
    }

    public static function getArguments(): array
    {
        return [];
    }

    public static function getOptions(): array
    {
        return [
            'campaign' => 'Only the subscriptions of this campaign (id)',
            'dry-run' => 'List the files to transfer, change nothing',
            'delay' => 'Pause between two files, in seconds (default 1)',
        ];
    }
}
