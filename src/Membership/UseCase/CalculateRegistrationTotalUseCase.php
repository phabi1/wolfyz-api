<?php

namespace App\Membership\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;

class CalculateRegistrationTotalUseCase implements UseCaseInterface
{
    private $campaignRepository;

    private $unitPrice = 0;

    private $licenses = [];

    public function __construct(EntityManager $entityManager)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
    }

    public function execute(array $params = []): array
    {
        $campaignId = $params['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID is required.');
        }

        $campaign = $this->campaignRepository->findById($campaignId);

        if (!$campaign) {
            throw new \InvalidArgumentException('Campaign not found.');
        }

        $this->licenses = $campaign->settings->licenses ?? [];
        $this->unitPrice = (int) $campaign->settings->contribution_amount ?? 0;
        
        $participants = $params['participants'] ?? [];
        
        if (count($participants) > 2) {
            $this->unitPrice = $campaign->settings->contribution_family_amount ?? 0; // Apply the same unit price for more than 2 participants
        }

        $discountAmount = 0;

        $items = [];

        foreach ($participants as $index => $participant) {

            $participant = is_array($participant) ? (object) $participant : $participant;

            $birthdate = $participant->birthdate ?? '';

            $items[] = [
                'type' => 'participant',
                'participant_index' => $index,
                'name' => 'Cotisation',
                'amount' => $this->unitPrice,
                'currency' => 'EUR',
            ];

            $license = $this->selectGoodLicense($birthdate);
            if ($license) {
                $items[] = [
                    'type' => 'fee',
                    'participant_index' => $index,
                    'name' => $license->title,
                    'amount' => $license->amount,
                    'currency' => 'EUR',
                ];
            }
        }

        if ($params['discount_amount'] > 0) {
            $discountAmount = (int) $params['discount_amount'];
            $items[] = [
                'type' => 'discount',
                'participant_index' => null,
                'name' => 'Remise',
                'amount' => -$discountAmount,
                'currency' => 'EUR',
            ];
        }

        return [
            'items' => $items,
            'total_amount' => (int) array_sum(array_column($items, 'amount')),
            'currency' => 'EUR',
        ];
    }

    private function selectGoodLicense(string $birthdate): \stdClass|null
    {
        if (empty($birthdate)) {
            return null;
        }

        if (empty($this->licenses)) {
            return null;
        }

        $licenses = $this->licenses;

        $birthDateTime = new \DateTime($birthdate);
        $year = (int) $birthDateTime->format('Y');

        foreach ($licenses as $license) {
            $yearMin = $license->year_min;
            $yearMax = $license->year_max;

            if (($yearMin === null || $year <= $yearMin) && ($yearMax === null || $year >= $yearMax)) {
                return $license;
            }
        }

        return null;
    }
}
