<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Membership\Entity\Repository\SessionEntityRepositoryInterface;

class GetRegistrationUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $campaignRepository;

    private EntityRepositoryInterface $lessonRepository;

    private SessionEntityRepositoryInterface $sessionRepository;

    private EntityRepositoryInterface $requestRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->lessonRepository = $entityManager->getRepository('wolf-memberships.lesson');
        $sessionRepository = $entityManager->getRepository('wolf-memberships.session');
        if (!$sessionRepository instanceof SessionEntityRepositoryInterface) {
            throw new \RuntimeException('Session repository must implement SessionEntityRepositoryInterface');
        }
        $this->sessionRepository = $sessionRepository;
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
    }

    public function execute(array $params = []): array
    {
        $campaignId = $params['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID is required.');
        }

        $campaign = $this->campaignRepository->findById($campaignId);

        if (!$campaign) {
            throw new \Exception('Campaign not found.');
        }

        $lessons = $this->lessonRepository->find(['campaign_id' => ['eq' => $campaignId]]);

        $this->calculateCompletude($lessons);

        $response = [
            'registration_start' => $campaign->registration_start,
            'registration_end' => $campaign->registration_end,
            'lessons' => $lessons
        ];

        if (!empty($params['request_id'])) {
            $token = $params['token'] ?? null;
            if (!$token) {
                throw new \InvalidArgumentException('Token is required for request retrieval.');
            }

            $request = $this->requestRepository->findById($params['request_id']);
            if (!$request) {
                throw new \Exception('Request not found.');
            }

            if ($request->token !== $token) {
                throw new \Exception('Invalid token for the request.');
            }

            $response['request'] = $request->data;
            $response['discount'] = $request->discount_amount ?? 0;
        }

        return $response;

    }

    private function calculateCompletude(array &$lessons): void
    {
        $lessonIds = array_map(fn($lesson) => $lesson->id, $lessons);
        $sessionCounts = $this->sessionRepository->countByLessons($lessonIds);

        foreach ($lessons as &$lesson) {
            $lesson->participant_nb = $sessionCounts[$lesson->id] ?? 0;
        }
    }
}