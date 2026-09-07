<?php

declare(strict_types=1);

namespace App\Test\Membership\UseCase;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Events\EventDispatcher;
use App\Core\Mail\Mailer;
use App\Membership\Event\RequestStatusChangedEvent;
use App\Membership\UseCase\MarkAsRejectedRequestUseCase;
use PHPUnit\Framework\TestCase;

final class MarkAsRejectedRequestUseCaseTest extends TestCase
{
    public function testExecuteRejectsPendingRequestAndDispatchesEvent(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $campaignRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestLogRepository = $this->createMock(EntityRepositoryInterface::class);
        $entityManager = $this->createEntityManager($campaignRepository, $requestRepository, $requestLogRepository);

        $mailer = $this->createMock(Mailer::class);
        $parameters = $this->createMock(Parameters::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $parameters->method('get')
            ->with('site_membership_request_edit_url')
            ->willReturn('https://club.local/edit');

        $request = (object) ['id' => 8, 'token' => 'req-token', 'status' => 'pending'];
        $campaign = (object) ['id' => 5, 'title' => 'Winter Camp'];
        $updatedRequest = (object) [
            'id' => 8,
            'email' => 'alice@example.com',
            'firstname' => 'Alice',
            'lastname' => 'Smith',
        ];

        $requestRepository->expects(self::once())
            ->method('findById')
            ->with(8)
            ->willReturn($request);
        $requestRepository->expects(self::once())
            ->method('update')
            ->with(8, ['status' => 'rejected'])
            ->willReturn($updatedRequest);
        $campaignRepository->expects(self::once())
            ->method('findById')
            ->with(5)
            ->willReturn($campaign);

        $requestLogRepository->expects(self::once())
            ->method('insert')
            ->with(self::callback(function (array $log): bool {
                return $log['request_id'] === 8
                    && $log['status'] === 'rejected'
                    && $log['params']['reason'] === 'Missing document'
                    && $log['changed_by'] === 4
                    && is_int($log['changed_at']);
            }))
            ->willReturn((object) []);

        $mailer->expects(self::once())
            ->method('sendMail')
            ->with(
                'alice@example.com',
                'membership/request-rejected',
                self::callback(function (array $vars): bool {
                    return $vars['campaignName'] === 'Winter Camp'
                        && $vars['reason'] === 'Missing document'
                        && $vars['editUrl'] === 'https://club.local/edit?campaign_id=5&request_id=8&token=req-token';
                })
            )
            ->willReturn(true);

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                'wolf_memberships_request_status_changed',
                self::callback(function ($event) use ($updatedRequest): bool {
                    return $event instanceof RequestStatusChangedEvent
                        && $event->getStatus() === 'rejected'
                        && $event->getRequest() === $updatedRequest;
                })
            );

        $useCase = new MarkAsRejectedRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 5,
            'request_id' => 8,
            'user_id' => 4,
            'reason' => 'Missing document',
        ]));
    }

    public function testExecuteThrowsWhenRequestIsNotPending(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestRepository->method('findById')->willReturn((object) ['status' => 'approved']);

        $useCase = new MarkAsRejectedRequestUseCase(
            $this->createEntityManager(
                $this->createMock(EntityRepositoryInterface::class),
                $requestRepository,
                $this->createMock(EntityRepositoryInterface::class)
            ),
            $this->createMock(Mailer::class),
            $this->createMock(Parameters::class),
            $this->createMock(EventDispatcher::class)
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending requests can be rejected.');

        $useCase->execute(['campaign_id' => 1, 'request_id' => 2]);
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
            ->with('site_membership_request_edit_url')
            ->willReturn('https://club.local/edit');

        $requestRepository->method('findById')->willReturn((object) ['id' => 9, 'token' => 'abc', 'status' => 'pending']);
        $campaignRepository->method('findById')->willReturn((object) ['id' => 2, 'title' => 'Winter Camp']);
        $updatedRequest = (object) [
            'id' => 9,
            'email' => 'alice@example.com',
            'firstname' => 'Alice',
            'lastname' => 'Smith',
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
                    return $event instanceof RequestStatusChangedEvent && $event->getStatus() === 'rejected';
                })
            );

        $useCase = new MarkAsRejectedRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 2,
            'request_id' => 9,
            'user_id' => 3,
            'reason' => 'missing',
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
