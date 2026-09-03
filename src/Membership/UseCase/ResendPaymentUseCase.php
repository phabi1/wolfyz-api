<?php

namespace App\Membership\UseCase;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Mail\Mailer;
use App\Core\UseCase\UseCaseInterface;

class ResendPaymentUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $campaignEntityRepository;

    private EntityRepositoryInterface $requestEntityRepository;
    private EntityRepositoryInterface $requestLogEntityRepository;
    private Mailer $mailService;
    private string $paymentPageUrl;

    public function __construct(EntityManager $entityManager, Mailer $mailService, Parameters $parameters)
    {
        $this->campaignEntityRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->requestEntityRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->requestLogEntityRepository = $entityManager->getRepository('wolf-memberships.request_log');
        $this->mailService = $mailService;
        $this->paymentPageUrl = $parameters->get('site_membership_request_payment_url');
    }

    public function execute(array $params = [])
    {
        $requestId = $params['request_id'] ?? null;
        if (!$requestId) {
            throw new \InvalidArgumentException('Request ID is required.');
        }
        $request = $this->requestEntityRepository->findById($requestId);
        if (!$request) {
            throw new \RuntimeException('Request not found.');
        }

        if (!$request->email) {
            throw new \RuntimeException('Request does not have an email.');
        }

        $campaign = $this->campaignEntityRepository->findById($request->campaign_id);
        if (!$campaign) {
            throw new \RuntimeException('Campaign not found.');
        }

        $paymentUrl = $this->buildPaymentUrl($campaign->id, $request);

         try {
            $this->mailService->sendMail(
                $request->email,
                'wolf-membership:request-resend-payment',
                [
                    'firstname' => $request->firstname,
                    'lastname' => $request->lastname,
                    'campaignName' => $campaign->title,
                    'request_id' => $requestId,
                    'paymentUrl' => $paymentUrl ?? null,
                ]
            );
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            error_log('Failed to send approval email: ' . $e->getMessage());
        }

        // Log the resend action
        $this->requestLogEntityRepository->insert([
            'request_id' => $requestId,
            'action' => 'resend_payment',
            'changed_at' => time(),
            'changed_by' => null,
        ]);
    }

    private function buildPaymentUrl($campaignId, $request)
    {
        return $this->paymentPageUrl . "?campaign_id={$campaignId}&request_id={$request->id}&token={$request->token}";
    }
}