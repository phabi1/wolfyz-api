<?php

namespace App\Membership\UseCase\Subscription;

use App\Core\Di\Locator;
use App\Core\Entity\EntityManager;
use App\Core\Entity\Exception\EntityNotFoundException;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Translation\Translator;
use App\Core\UseCase\UseCaseInterface;
use App\Membership\Entity\Repository\MemberEntityRepository;

class ExportSubscriptionsUseCase implements UseCaseInterface
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';

    /**
     * @var EntityRepositoryInterface
     */
    private $campaignRepository;

    /**
     * @var MemberEntityRepository 
     */
    private $memberRepository;
    /**
     * Summary of subscriptionRepository
     * @var EntityRepositoryInterface
     */
    private $subscriptionRepository;

    private $dateHelper;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(EntityManager $entityManager, Locator $helpers, Translator $translator)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->memberRepository = $entityManager->getRepository('wolf-memberships.member');
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
        $this->dateHelper = $helpers->get('date');
        $this->translator = $translator;
    }
    public function execute(array $params = []): mixed
    {
        $params += [
            'filters' => [],
            'reverse' => false,
            'ids' => [],
            'format' => self::FORMAT_XLSX
        ];

        $campaignId = $params['campaign_id'] ?? null;

        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID parameter is required');
        }

        if (!\in_array($params['format'], [self::FORMAT_CSV, self::FORMAT_XLSX])) {
            throw new \InvalidArgumentException('Unsupported export format: ' . $params['format']);
        }

        $conditions = [];
        if (isset($params['filters'])) {
            $conditions += $params['filters'];
        }
        if (isset($params['ids']) && !empty($params['ids'])) {
            if ($params['reverse']) {
                $conditions['id'] = ['not_in' => $params['ids']];
            } else {
                $conditions['id'] = ['in' => $params['ids']];
            }
        }
        $conditions['campaign_id'] = ['eq' => $campaignId];

        $campaign = $this->campaignRepository->findById($campaignId);
        if (!$campaign) {
            throw new EntityNotFoundException('wolf-memberships.campaign', 'Campaign not found: ' . $campaignId);
        }

        $subscriptions = $this->subscriptionRepository->find($conditions);

        $memberIds = array_unique(array_map(fn($subscription) => $subscription->member_id, $subscriptions));

        $members = array_reduce($this->memberRepository->findByIds($memberIds), function ($carry, $member) {
            $carry[$member->id] = $member;
            return $carry;
        }, []);


        $baseHeaders = [
            ['name' => 'id', 'label' => $this->translator->translate('membership.subscriptions.export.id')],
            ['name' => 'license_type', 'label' => $this->translator->translate('membership.subscriptions.export.license_type')],
            ['name' => 'license_taken_at', 'label' => $this->translator->translate('membership.subscriptions.export.license_taken_at')],
            ['name' => 'subscribed_at', 'label' => $this->translator->translate('membership.subscriptions.export.subscribed_at')],
            ['name' => 'campaign_id', 'label' => $this->translator->translate('membership.subscriptions.export.campaign_id')],
            ['name' => 'member_id', 'label' => $this->translator->translate('membership.subscriptions.export.member_id')],
            ['name' => 'firstName', 'label' => $this->translator->translate('membership.subscriptions.export.firstName')],
            ['name' => 'lastName', 'label' => $this->translator->translate('membership.subscriptions.export.lastName')],
            ['name' => 'birthdate', 'label' => $this->translator->translate('membership.subscriptions.export.birthdate')],
            ['name' => 'email', 'label' => $this->translator->translate('membership.subscriptions.export.email')],
            ['name' => 'license_number', 'label' => $this->translator->translate('membership.subscriptions.export.license_number')],
        ];

        $customFields = $campaign->settings->custom_fields ?? [];
        if (!is_array($customFields)) {
            throw new \UnexpectedValueException('Campaign custom fields must be an array.');
        }

        $fieldLabels = [];
        foreach ($customFields as $customField) {
            if (!is_object($customField) || !isset($customField->name, $customField->label)
                || !is_string($customField->name) || $customField->name === ''
                || !is_string($customField->label)) {
                throw new \UnexpectedValueException('Invalid campaign custom field definition.');
            }
            if (array_key_exists($customField->name, $fieldLabels)) {
                throw new \UnexpectedValueException('Duplicate campaign custom field: ' . $customField->name);
            }
            $fieldLabels[$customField->name] = $customField->label;
        }

        foreach ($subscriptions as $subscription) {
            foreach ((array) ($subscription->fields ?? []) as $name => $value) {
                if (!array_key_exists($name, $fieldLabels)) {
                    $translationKey = 'membership.subscriptions.export.fields.' . $name;
                    $label = $this->translator->translate($translationKey);
                    $fieldLabels[$name] = $label === $translationKey ? $name : $label;
                }
            }
        }

        $rows = [];
        foreach ($subscriptions as $subscription) {
            $member = $members[$subscription->member_id] ?? null;
            if (!$member) {
                continue;
            }

            $row = [
                'id' => $subscription->id,
                'license_type' => $subscription->license_type,
                'license_taken_at' => $subscription->license_taken_at ? $this->dateHelper->format($subscription->license_taken_at) : null,
                'subscribed_at' => $subscription->subscribed_at ? $this->dateHelper->format($subscription->subscribed_at) : null,
                'campaign_id' => $subscription->campaign_id,
                'member_id' => $subscription->member_id,
                'firstName' => $member->firstname,
                'lastName' => $member->lastname,
                'birthdate' => $member->birthdate ? $this->dateHelper->format($member->birthdate) : null,
                'email' => $member->email,
                'license_number' => $member->license_number,
            ];
            $values = (array) ($subscription->fields ?? []);
            foreach ($fieldLabels as $name => $label) {
                $row['fields.' . $name] = $this->formatValue($values[$name] ?? null);
            }
            $rows[] = $row;
        }

        $headers = [
            ...$baseHeaders,
        ];
        foreach ($fieldLabels as $name => $label) {
            $headers[] = ['name' => 'fields.' . $name, 'label' => $label];
        }

        $exportData = array_map(
            fn(array $row) => array_map(fn($header) => $row[$header['name']] ?? null, $headers),
            $rows
        );

        if ($params['format'] === self::FORMAT_CSV) {
            $export = new Export\CsvExport();
        } else if ($params['format'] === self::FORMAT_XLSX) {
            $export = new Export\XlsxExport();
        } else {
            throw new \InvalidArgumentException('Unsupported export format: ' . $params['format']);
        }


        $file = $export->export(array_column($headers, 'label'), $exportData);

        return [
            'file_url' => $file,
            'format' => $params['format']
        ];
    }
    private function formatValue(mixed $value): mixed
    {
        if (\is_array($value) || \is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        if (\is_bool($value)) {
            return $value ? 1 : 0;
        }
        return $value;
    }
}
