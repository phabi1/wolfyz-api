<?php

namespace App\Membership\Dashboard\Source;

use App\Core\UseCase\UseCaseBus;
use App\Membership\Dashboard\SourceBusInterface;

class LessonsCompletude implements SourceBusInterface
{
    private $useCaseBus;

    public function __construct(UseCaseBus $useCaseBus)
    {
        $this->useCaseBus = $useCaseBus;
    }

    public function source(array $data = []): array
    {
        $campaignId = $data['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \Exception("Campaign ID is required for lesson completude source");
        }

        $result = $this->useCaseBus->execute('wolf-memberships.get_lessons_completude', [
            'campaign_id' => $campaignId
        ]);

        return [
            'sessions' => $result
        ];
    }
}
