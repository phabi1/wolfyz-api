<?php

declare(strict_types=1);

namespace App\Test\Membership\UseCase;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Events\EventDispatcher;
use App\Core\Mail\Mailer;
use App\Membership\Event\RequestStatusChangedEvent;
use App\Membership\UseCase\MarkAsApprovedRequestUseCase;
use PHPUnit\Framework\TestCase;

final class MarkAsApprovedRequestUseCaseTest extends TestCase
{
    public function testExecuteApprovesRequestAndDispatchesEvent(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $campaignRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestLogRepository = $this->createMock(EntityRepositoryInterface::class);
        $entityManager = $this->createEntityManager($campaignRepository, $requestRepository, $requestLogRepository);

        $mailer = $this->createMock(Mailer::class);
        $parameters = $this->createMock(Parameters::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $parameters->method('get')
            ->with('site_membership_request_payment_url')
            ->willReturn('https://pay.local/checkout');

        $request = (object) ['id' => 42, 'status' => 'pending'];
        $campaign = (object) ['title' => 'Camp'];
        $updatedRequest = (object) [
            'id' => 42,
            'token' => 'abc-token',
            'email' => 'john@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];

        $requestRepository->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn($request);
        $requestRepository->expects(self::once())
            ->method('update')
            ->with(42, ['status' => 'approved'])
            ->willReturn($updatedRequest);
        $campaignRepository->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($campaign);

        $requestLogRepository->expects(self::once())
            ->method('insert')
            ->with(self::callback(function (array $log): bool {
                return $log['request_id'] === 42
                    && $log['status'] === 'approved'
                    && $log['changed_by'] === 7
                    && is_int($log['changed_at']);
            }))
            ->willReturn((object) []);

        $mailer->expects(self::once())
            ->method('sendMail')
            ->with(
                'john@example.com',
                'membership/request-approved',
                self::callback(function (array $vars): bool {
                    return $vars['request_id'] === 42
                        && $vars['campaignName'] === 'Camp'
                        && $vars['paymentUrl'] === 'https://pay.local/checkout?campaign_id=10&request_id=42&token=abc-token';
                })
            )
            ->willReturn(true);

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                'wolf_memberships_request_status_changed',
                self::callback(function ($event) use ($updatedRequest): bool {
                    return $event instanceof RequestStatusChangedEvent
                        && $event->getStatus() === 'approved'
                        && $event->getRequest() === $updatedRequest;
                })
            );

        $useCase = new MarkAsApprovedRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 10,
            'request_id' => 42,
            'user_id' => 7,
        ]));
    }

    public function testExecuteThrowsWhenCampaignIdIsMissing(): void
    {
        $useCase = new MarkAsApprovedRequestUseCase(
            $this->createEntityManager(
                $this->createMock(EntityRepositoryInterface::class),
                $this->createMock(EntityRepositoryInterface::class),
                $this->createMock(EntityRepositoryInterface::class)
            ),
            $this->createMock(Mailer::class),
            $this->createMock(Parameters::class),
            $this->createMock(EventDispatcher::class)
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Campaign ID is required.');

        $useCase->execute(['request_id' => 1]);
    }

    public function testExecuteContinuesWhenMailerThrows(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $campaignRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestLogRepository = $this->createMock(EntityRepositoryInterface::class);
        $entityManager = $this->createEntityManager($campaignRepository, $requestRepository, $requestLogRepository);

        $mailer = $this->createMock(Mailer::class);
        $parameters = $this->createMock(Parameters::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $parameters->method('get')
            ->with('site_membership_request_payment_url')
            ->willReturn('https://pay.local/checkout');

        $requestRepository->method('findById')->willReturn((object) ['id' => 11, 'status' => 'pending']);
        $campaignRepository->method('findById')->willReturn((object) ['title' => 'Camp']);
        $updatedRequest = (object) [
            'id' => 11,
            'token' => 'tkn',
            'email' => 'john@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];
        $requestRepository->method('update')->willReturn($updatedRequest);
        $requestLogRepository->method('insert')->willReturn((object) []);

        $mailer->expects(self::once())
            ->method('sendMail')
            ->willThrowException(new \RuntimeException('smtp down'));

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                'wolf_memberships_request_status_changed',
                self::callback(function ($event): bool {
                    return $event instanceof RequestStatusChangedEvent && $event->getStatus() === 'approved';
                })
            );

        $useCase = new MarkAsApprovedRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 3,
            'request_id' => 11,
            'user_id' => 1,
        ]));
    }

    private function createEntityManager(
        EntityRepositoryInterface $campaignRepository,
        EntityRepositoryInterface $requestRepository,
        EntityRepositoryInterface $requestLogRepository
    ): EntityManager {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(function (string $name) use ($campaignRepository, $requestRepository, $requestLogRepository) {
                return match ($name) {
                    'wolf-memberships.campaign' => $campaignRepository,
                    'wolf-memberships.request' => $requestRepository,
                    'wolf-memberships.request_log' => $requestLogRepository,
                    default => throw new \InvalidArgumentException('Unexpected repository ' . $name),
                };
            });

        return $entityManager;
    }
}
