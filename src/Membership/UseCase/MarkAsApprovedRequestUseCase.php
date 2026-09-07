<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Core\Mail\Mailer;
use App\Core\Config\Parameters;
use App\Core\Events\EventDispatcher;
use App\Membership\Event\RequestStatusChangedEvent;

class MarkAsApprovedRequestUseCase implements UseCaseInterface
{
    private $campaignRepository;

    private $requestRepository;

    private EntityRepositoryInterface $requestLogRepository;

    private $mailService;
    private $paymentPageUrl;
    private $eventDispatcher;

    public function __construct(EntityManager $entityManager, Mailer $mailService, Parameters $parameters, EventDispatcher $eventDispatcher)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->requestLogRepository = $entityManager->getRepository('wolf-memberships.request_log');
        $this->mailService = $mailService;
        $this->paymentPageUrl = $parameters->get('site_membership_request_payment_url');
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

        if ($request->status !== 'pending' && $request->status !== 'rejected') {
            throw new \Exception('Only pending or rejected requests can be approved.');
        }

        $campaign = $this->campaignRepository->findById($campaignId);

        // Update the request status to 'approved'
        $updatedRequest = $this->requestRepository->update($requestId, [
            'status' => 'approved',
        ]);

        $this->requestLogRepository->insert([
            'request_id' => $requestId,
            'status' => 'approved',
            'changed_at' => time(),
            'changed_by' => $params['user_id'] ?? null,
        ]);

        // Generate a payment URL (this is just a placeholder, implement your own logic)
        $paymentUrl = $this->buildPaymentUrl($campaignId, $updatedRequest);

        // Send an email notification to the user
        try {
            $this->mailService->sendMail(
                $updatedRequest->email,
                'membership/request-approved',
                [
                    'firstname' => $updatedRequest->firstname,
                    'lastname' => $updatedRequest->lastname,
                    'campaignName' => $campaign->title,
                    'request_id' => $requestId,
                    'paymentUrl' => $paymentUrl ?? null,
                ]
            );
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            error_log('Failed to send approval email: ' . $e->getMessage());
        }

        $this->eventDispatcher->dispatch(
            RequestStatusChangedEvent::EVENT,
            new RequestStatusChangedEvent($updatedRequest, 'approved')
        );

        return [];
    }

    private function buildPaymentUrl($campaignId, $request)
    {
        if (!$this->paymentPageUrl) {
            throw new \Exception('Payment page is not configured.');
        }
        return $this->paymentPageUrl . "?campaign_id={$campaignId}&request_id={$request->id}&token={$request->token}";
    }
}