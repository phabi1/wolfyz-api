<?php

namespace App\Membership\UseCase\Request;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Mail\Mailer;
use App\Core\UseCase\UseCaseBus;
use App\Core\UseCase\UseCaseInterface;
use App\Membership\Request\Invoice;

class SendInvoiceEmailFromRequestUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $campaignRepository;

    private EntityRepositoryInterface $requestRepository;

    private UseCaseBus $useCaseBus;

    private Mailer $mailService;
    private Invoice $invoice;

    public function __construct(EntityManager $entityManager, UseCaseBus $useCaseBus, Mailer $mailService, Invoice $invoice)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->useCaseBus = $useCaseBus;
        $this->mailService = $mailService;
        $this->invoice = $invoice;
    }

    public function execute(array $params = []): array
    {
        $campaignId = (int) ($params['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            throw new \InvalidArgumentException('Campaign ID is required.');
        }

        $requestId = (int) ($params['request_id'] ?? 0);
        if ($requestId <= 0) {
            throw new \InvalidArgumentException('Request ID is required.');
        }

        $campaign = $this->campaignRepository->findById($campaignId);
        if (!$campaign) {
            throw new \Exception('Campaign not found.');
        }

        $request = $this->requestRepository->findById($requestId);
        if (!$request) {
            throw new \Exception('Request not found.');
        }

        if ((int) $request->campaign_id !== $campaignId) {
            throw new \Exception('Request does not belong to the provided campaign.');
        }

        if ($request->status !== 'approved' && $request->status !== 'paid') {
            throw new \Exception('Only approved or paid requests can receive invoice email.');
        }

        $downloadUrl = $this->invoice->url($request);

        $sent = $this->mailService->sendMail(
            $request->email,
            'membership/request-invoice',
            [
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'campaignName' => $campaign->title,
                'invoiceAmount' => $request->total_amount,
                'invoiceCurrency' => 'EUR',
                'invoiceDownloadUrl' => $downloadUrl,
                'requestId' => $requestId,
            ]
        );

        if (!$sent) {
            throw new \RuntimeException('Failed to send invoice email.');
        }

        return [
            'request_id' => $request->id,
            'email' => $request->email,
        ];
    }
}
