<?php

namespace App\Membership\UseCase\Request;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Core\Mail\Mailer;
use App\Core\Events\EventDispatcher;
use App\Membership\Event\RequestStatusChangedEvent;
use App\Membership\Request\Invoice;

class MarkAsPaidRequestUseCase implements UseCaseInterface
{
    private $campaignRepository;

    private $requestRepository;

    private EntityRepositoryInterface $requestLogRepository;

    private $mailService;
    private EventDispatcher $eventDispatcher;

    private $contactPageUrl;

    private Invoice $invoice;

    public function __construct(EntityManager $entityManager, Mailer $mailService, Parameters $parameters, EventDispatcher $eventDispatcher, Invoice $invoice)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->requestLogRepository = $entityManager->getRepository('wolf-memberships.request_log');
        $this->mailService = $mailService;
        $this->eventDispatcher = $eventDispatcher;
        $this->contactPageUrl = $parameters->get('site_contact_url');
        $this->invoice = $invoice;
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

        if ($request->status !== 'approved' && $request->status !== 'rejected') {
            throw new \Exception('Only approved or rejected requests can be marked as paid.');
        }

        $campaign = $this->campaignRepository->findById($campaignId);

        // Update the request status to 'paid'
        $updatedRequest = $this->requestRepository->update($requestId, [
            'status' => 'paid',
        ]);

        $this->requestLogRepository->insert([
            'request_id' => $requestId,
            'status' => 'paid',
            'changed_at' => time(),
            'changed_by' => $params['user_id'] ?? null,
        ]);

        // Generate a payment URL (this is just a placeholder, implement your own logic)
        $contactUrl = $this->buildContactUrl();

        // Send an email notification to the user
        try {
            $this->mailService->sendMail(
                $updatedRequest->email,
                'membership/request-paid',
                [
                    'firstname' => $updatedRequest->firstname,
                    'lastname' => $updatedRequest->lastname,
                    'campaignName' => $campaign->title,
                    'request_id' => $requestId,
                    'invoiceUrl' => $this->invoice->url($updatedRequest),
                    'contactUrl' => $contactUrl ?? null,
                ]
            );
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            error_log('Failed to send paid email: ' . $e->getMessage());
        }

        $this->eventDispatcher->dispatch(
            RequestStatusChangedEvent::EVENT,
            new RequestStatusChangedEvent($updatedRequest, 'paid')
        );

        return [];
    }

    private function buildContactUrl()
    {
        if (!$this->contactPageUrl) {
            throw new \Exception('Contact page URL is not configured.');
        }
        return $this->contactPageUrl;
    }
}