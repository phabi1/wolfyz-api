<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepository;
use App\Core\UseCase\UseCaseInterface;
use App\Membership\Print\PresenceList;

class PrintPeriodUseCase implements UseCaseInterface
{
    /**
     * Summary of PeriodRepository
     * @var EntityRepository
     */
    private EntityRepository $periodRepository;

    /**
     * Summary of lessonRepository
     * @var EntityRepository
     */
    private EntityRepository $lessonRepository;

    private EntityRepository $sessionRepository;

    /**
     * Summary of MemberRepository
     * @var EntityRepository
     */
    private EntityRepository $memberRepository;
    private EntityRepository $contactRepository;
    private EntityRepository $subscriptionRepository;


    public function __construct(EntityManager $entityManager)
    {
        $this->periodRepository = $entityManager->getRepository('wolf-memberships.period');
        $this->lessonRepository = $entityManager->getRepository('wolf-memberships.lesson');
        $this->sessionRepository = $entityManager->getRepository('wolf-memberships.session');
        $this->memberRepository = $entityManager->getRepository('wolf-memberships.member');
        $this->contactRepository = $entityManager->getRepository('wolf-memberships.contact');
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
    }

    public function execute(array $params = [])
    {
        $periodId = $params['id'];
        $period = $this->periodRepository->findById($periodId);
        $lessons = $this->lessonRepository->find([
            'campaign_id' => ['eq' => $period->campaign_id],
        ]);

        $sessions = $this->sessionRepository->find([
            'campaign_id' => ['eq' => $period->campaign_id],
        ]);

        $memberIds = array_unique(array_map(function ($session) {
            return $session->member_id;
        }, $sessions));

        $subscriptionIds = array_unique(array_map(function ($session) {
            return $session->subscription_id;
        }, $sessions));

        $members = array_reduce($this->memberRepository->find([
            'id' => [
                'in' => $memberIds
            ],
        ]), function ($carry, $member) {
            $carry[$member->id] = $member;
            return $carry;
        }, []);

        $contacts = $this->contactRepository->find([
            'subscription_id' => [
                'in' => $subscriptionIds
            ],
        ]);

        foreach ($contacts as $contact) {
            if (isset($members[$contact->subscription_id])) {
                $members[$contact->subscription_id]->phone = $contact->phone;
            }
        }

        $subscriptions = [];
        if (!empty($subscriptionIds)) {
            foreach ($this->subscriptionRepository->find([
                'id' => ['in' => array_values($subscriptionIds)],
            ]) as $subscription) {
                $subscriptions[$subscription->id] = $subscription;
            }
        }

        $membersByLessons = [];

        foreach ($lessons as $lesson) {
            $membersByLessons[$lesson->id] = array_map(function ($session) use ($members, $subscriptions) {
                $member = $members[$session->member_id] ?? null;
                if ($member === null) {
                    return null;
                }
                // Authorizations belong to the subscription, not the member
                $row = clone $member;
                $subscription = $subscriptions[$session->subscription_id] ?? null;
                $row->agree_exit = $subscription->agree_exit ?? null;
                $row->agree_image = $subscription->agree_image ?? null;
                return $row;
            }, array_filter($sessions, function ($session) use ($lesson) {
                return $session->lesson_id === $lesson->id;
            }));
        }

        foreach ($membersByLessons as $lessonId => $members) {
            if (empty($members)) {
                continue;
            }
            usort($members, function ($a, $b) {
                if ($a === null) return 1;
                if ($b === null) return -1;
                return strcmp($a->lastname . ' ' . $a->firstname, $b->lastname . ' ' . $b->firstname);
            });
            $membersByLessons[$lessonId] = $members;
        }

        $pdf = new PresenceList();
        $pdf->setPeriod($period);
        $pdf->setLessons($lessons);
        $pdf->setMembers($membersByLessons);

        $pdfContent = $pdf->render();
        return [
            'pdf' => $pdfContent,
            'filename' => $this->generateFilename($period)
        ];
    }

    protected function generateFilename($period)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $period->title);
        return $filename . '.pdf';
    }
}