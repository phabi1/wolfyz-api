<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseBus;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Core\Mail\Mailer;

class RegisterToCampaignUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $campaignRepository;

    private EntityRepositoryInterface $requestRepository;

    private EntityRepositoryInterface $requestLogRepository;

    private Mailer $mailService;

    public function __construct(EntityManager $entityManager, Mailer $mailService)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
        $this->requestLogRepository = $entityManager->getRepository('wolf-memberships.request_log');
        $this->mailService = $mailService;
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

        if ($campaign->registration_start && $campaign->registration_end) {
            $now = new \DateTime();
            $start = new \DateTime($campaign->registration_start);
            $end = new \DateTime($campaign->registration_end);

            if ($now < $start || $now > $end) {
                throw new \Exception('Registration is not open for this campaign.');
            }
        }


        $request = $this->requestRepository->insert([
            'status' => 'pending',
            'firstname' => $params['contact']['firstname'] ?? null,
            'lastname' => $params['contact']['lastname'] ?? null,
            'email' => $params['contact']['email'] ?? null,
            'phone' => $params['contact']['phone'] ?? null,
            'data' => $params['data'] ?? [],
            'token' => bin2hex(random_bytes(16)), // Generate a random token
            'campaign_id' => $campaignId,
        ]);

        $this->requestLogRepository->insert([
            'request_id' => $request->id,
            'status' => 'pending',
            'changed_at' => time(),
        ]);

        if ($this->sendConfirmationEmail($campaign, $request) === false) {
            throw new \Exception('Failed to send confirmation email.');
        }

        if ($this->sendNewRequestEmail($campaign, $request) === false) {
            throw new \Exception('Failed to send new request email.');
        }

       

        return [
            'request_id' => $request->id,
        ];
    }

    private function buildEditUrl($campaign, $request): string
    {
        $pageId = get_option('wolf_membership_registration_page');

        return get_permalink($pageId) . "?campaign_id={$campaign->id}&request_id={$request->id}&token={$request->token}";
    }

    private function sendConfirmationEmail($campaign, $request): bool
    {
        $email = $request->email;
        $context = ['editUrl' => $this->buildEditUrl($campaign, $request)];
        return $this->mailService->sendMail($email, 'wolf-membership:request-confirmation', $context);

    }

    private function sendNewRequestEmail($campaign, $request): bool
    {
        $email = 'phabi1@hotmail.fr';
        $context = [
            'campaignName' => $campaign->name,
            'memberName' => $request->firstname . ' ' . $request->lastname,
            'memberEmail' => $request->email,
            'requestId' => $request->id,
            'adminUrl' => admin_url('admin.php?page=wolf-memberships#/campaign/' . $campaign->id . '/requests/' . $request->id),
        ];
        return $this->mailService->sendMail($email, 'wolf-membership:new-request', $context);
    }
}