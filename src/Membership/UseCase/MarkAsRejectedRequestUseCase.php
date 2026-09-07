<?php

namespace App\Membership\UseCase;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Events\EventDispatcher;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Core\Mail\Mailer;
use App\Membership\Event\RequestStatusChangedEvent;

class MarkAsRejectedRequestUseCase implements UseCaseInterface
{
    private $campaignRepository;
    private $requestRepository;
    private EventDispatcher $eventDispatcher;

    private EntityRepositoryInterface $requestLogRepository;

    private $mailService;

    private $editUrl;

    public function __construct(
        EntityManager $entityManager,
        Mailer $mailService,
        Parameters $parameters,
        EventDispatcher $eventDispatcher
    ) {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->requestLogRepository = $entityManager->getRepository('wolf-memberships.request_log');
        $this->mailService = $mailService;
        $this->editUrl = $parameters->get('site_membership_request_edit_url');
        $this->eventDispatcher = $eventDispatcher;
    }

    public function execute(array $params = []): array
    {
        $campaignId = $params['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID is required.');
        }

        $requestId = $params['request_id'] ?? null;
        if (!$requestId) {
            throw new \InvalidArgumentException('Request ID is required.');
        }

        // Fetch the request from the repository
        $request = $this->requestRepository->findById($requestId);
        if (!$request) {
            throw new \Exception('Request not found.');
        }

        if ($request->status !== 'pending') {
            throw new \Exception('Only pending requests can be rejected.');
        }

        $campaign = $this->campaignRepository->findById($campaignId);

        // Update the request status to 'rejected'
        $updatedRequest = $this->requestRepository->update($requestId, [
            'status' => 'rejected',
        ]);

        $this->requestLogRepository->insert([
            'request_id' => $requestId,
            'status' => 'rejected',
            'params' => [
                'reason' => $params['reason'] ?? '',
            ],
            'changed_at' => time(),
            'changed_by' => $params['user_id'] ?? null,
        ]);

        $editUrl = $this->buildEditUrl($campaign, $request);

        // Send an email notification to the user
        try {
            $this->mailService->sendMail(
                $updatedRequest->email,
                'membership/request-rejected',
                [
                    'firstname' => $updatedRequest->firstname,
                    'lastname' => $updatedRequest->lastname,
                    'campaignName' => $campaign->title,
                    'reason' => $params['reason'] ?? '',
                    'editUrl' => $editUrl,
                ]
            );
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            error_log('Failed to send rejection email: ' . $e->getMessage());
        }

        $this->eventDispatcher->dispatch(
            RequestStatusChangedEvent::EVENT,
            new RequestStatusChangedEvent($updatedRequest, 'rejected')
        );

        return [];
    }

    private function buildEditUrl($campaign, $request): string
    {
        return $this->editUrl . "?campaign_id={$campaign->id}&request_id={$request->id}&token={$request->token}";
    }
}