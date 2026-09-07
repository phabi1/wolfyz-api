<?php

declare(strict_types=1);

namespace App\Test\Membership\UseCase;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Events\EventDispatcher;
use App\Core\Mail\Mailer;
use App\Membership\Event\RequestStatusChangedEvent;
use App\Membership\UseCase\MarkAsCancelledRequestUseCase;
use PHPUnit\Framework\TestCase;

final class MarkAsCancelledRequestUseCaseTest extends TestCase
{
    public function testExecuteCancelsRequestAndDispatchesEvent(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestLogRepository = $this->createMock(EntityRepositoryInterface::class);
        $entityManager = $this->createEntityManager($requestRepository, $requestLogRepository);

        $mailer = $this->createMock(Mailer::class);
        $parameters = $this->createMock(Parameters::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $parameters->method('get')
            ->with('site_contact_url')
            ->willReturn('https://club.local/contact');

        $request = (object) ['id' => 3, 'status' => 'approved'];
        $updatedRequest = (object) [
            'id' => 3,
            'email' => 'user@example.com',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ];

        $requestRepository->expects(self::once())
            ->method('findById')
            ->with(3)
            ->willReturn($request);
        $requestRepository->expects(self::once())
            ->method('update')
            ->with(3, ['status' => 'cancelled'])
            ->willReturn($updatedRequest);

        $requestLogRepository->expects(self::once())
            ->method('insert')
            ->with(self::callback(function (array $log): bool {
                return $log['request_id'] === 3
                    && $log['status'] === 'cancelled'
                    && $log['changed_by'] === 12
                    && is_int($log['changed_at']);
            }))
            ->willReturn((object) []);

        $mailer->expects(self::once())
            ->method('sendMail')
            ->with(
                'user@example.com',
                'membership/request-cancelled',
                self::callback(function (array $vars): bool {
                    return $vars['campaign_id'] === 7
                        && $vars['request_id'] === 3
                        && $vars['firstname'] === 'Jane';
                })
            )
            ->willReturn(true);

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                'wolf_memberships_request_status_changed',
                self::callback(function ($event) use ($updatedRequest): bool {
                    return $event instanceof RequestStatusChangedEvent
                        && $event->getStatus() === 'cancelled'
                        && $event->getRequest() === $updatedRequest;
                })
            );

        $useCase = new MarkAsCancelledRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 7,
            'request_id' => 3,
            'user_id' => 12,
        ]));
    }

    public function testExecuteThrowsWhenRequestIsPaid(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestRepository->method('findById')->willReturn((object) ['status' => 'paid']);

        $useCase = new MarkAsCancelledRequestUseCase(
            $this->createEntityManager(
                $requestRepository,
                $this->createMock(EntityRepositoryInterface::class)
            ),
            $this->createMock(Mailer::class),
            $this->createMock(Parameters::class),
            $this->createMock(EventDispatcher::class)
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Paid requests cannot be cancelled.');

        $useCase->execute([
            'campaign_id' => 1,
            'request_id' => 2,
        ]);
    }

    public function testExecuteContinuesWhenMailerThrows(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestLogRepository = $this->createMock(EntityRepositoryInterface::class);
        $entityManager = $this->createEntityManager($requestRepository, $requestLogRepository);

        $mailer = $this->createMock(Mailer::class);
        $parameters = $this->createMock(Parameters::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $parameters->method('get')
            ->with('site_contact_url')
            ->willReturn('https://club.local/contact');

        $requestRepository->method('findById')->willReturn((object) ['id' => 4, 'status' => 'approved']);
        $updatedRequest = (object) [
            'id' => 4,
            'email' => 'user@example.com',
            'firstname' => 'Jane',
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
                    return $event instanceof RequestStatusChangedEvent && $event->getStatus() === 'cancelled';
                })
            );

        $useCase = new MarkAsCancelledRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 10,
            'request_id' => 4,
            'user_id' => 3,
        ]));
    }

    private function createEntityManager(
        EntityRepositoryInterface $requestRepository,
        EntityRepositoryInterface $requestLogRepository
    ): EntityManager {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(function (string $name) use ($requestRepository, $requestLogRepository) {
                return match ($name) {
                    'wolf-memberships.request' => $requestRepository,
                    'wolf-memberships.request_log' => $requestLogRepository,
                    default => throw new \InvalidArgumentException('Unexpected repository ' . $name),
                };
            });

        return $entityManager;
    }
}
