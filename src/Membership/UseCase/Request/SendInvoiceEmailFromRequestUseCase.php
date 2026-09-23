<?php

namespace App\Membership\UseCase\Request;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Mail\Mailer;
use App\Core\Config\Parameters;
use App\Core\UseCase\UseCaseBus;
use App\Core\UseCase\UseCaseInterface;

class SendInvoiceEmailFromRequestUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $campaignRepository;

    private EntityRepositoryInterface $requestRepository;

    private UseCaseBus $useCaseBus;

    private Mailer $mailService;

    private Parameters $parameters;

    public function __construct(EntityManager $entityManager, UseCaseBus $useCaseBus, Mailer $mailService, Parameters $parameters)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->useCaseBus = $useCaseBus;
        $this->mailService = $mailService;
        $this->parameters = $parameters;
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

        $expires = time() + (7 * 24 * 60 * 60);
        $downloadUrl = $this->buildDownloadUrl($campaignId, $requestId, $request->token);

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
            'expires_at' => $expires,
        ];
    }

    private function buildDownloadUrl(int $campaignId, int $requestId, string $token): string
    {
        $baseUrl = rtrim((string) $this->parameters->get('base_url', ''), '/');
        if ($baseUrl === '') {
            throw new \RuntimeException('BASE_URL must be configured to send invoice download links.');
        }

        $path = '/membership/campaigns/' . $campaignId . '/requests/' . $requestId . '/invoice/download';
        return $baseUrl . $path . '?token=' . $token;
    }
}
