<?php

namespace App\Membership\UseCase\Subscription;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;

/**
 * Marks (or unmarks) the license as taken for a selection of subscriptions.
 *
 * Selection follows the datagrid semantics: `ids` are the selected rows, or
 * the excluded rows when `reverse` is true.
 */
class TakeLicenseUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $subscriptionRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
    }

    public function execute(array $params = []): mixed
    {
        $params += [
            'ids' => [],
            'reverse' => false,
            'taken_at' => null,
            'only_missing' => true,
        ];

        $campaignId = $params['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID parameter is required');
        }

        $ids = array_values(array_filter(array_map('intval', (array) $params['ids'])));
        $reverse = (bool) $params['reverse'];

        if (!$reverse && empty($ids)) {
            return ['updated' => 0];
        }

        $conditions = ['campaign_id' => ['eq' => $campaignId]];
        if (!empty($ids)) {
            $conditions['id'] = [$reverse ? 'not_in' : 'in' => $ids];
        }

        $takenAt = $params['taken_at'] !== null ? (int) $params['taken_at'] : null;

        $updated = 0;
        foreach ($this->subscriptionRepository->find($conditions) as $subscription) {
            // Do not overwrite an existing date when marking as taken
            if ($takenAt !== null && $params['only_missing'] && $subscription->license_taken_at) {
                continue;
            }
            $this->subscriptionRepository->update($subscription->id, ['license_taken_at' => $takenAt]);
            $updated++;
        }

        return ['updated' => $updated];
    }
}
