<?php

declare(strict_types=1);

namespace App\Test\Membership\UseCase;

use App\Core\Config\Parameters;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Events\EventDispatcher;
use App\Core\Mail\Mailer;
use App\Membership\Event\RequestStatusChangedEvent;
use App\Membership\UseCase\MarkAsPaidRequestUseCase;
use PHPUnit\Framework\TestCase;

final class MarkAsPaidRequestUseCaseTest extends TestCase
{
    public function testExecuteMarksRequestAsPaidAndDispatchesEvent(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $campaignRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestLogRepository = $this->createMock(EntityRepositoryInterface::class);
        $entityManager = $this->createEntityManager($campaignRepository, $requestRepository, $requestLogRepository);

        $mailer = $this->createMock(Mailer::class);
        $parameters = $this->createMock(Parameters::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $parameters->method('get')
            ->with('site_contact_url')
            ->willReturn('https://club.local/contact');

        $request = (object) ['id' => 12, 'status' => 'approved'];
        $campaign = (object) ['title' => 'Summer Camp'];
        $updatedRequest = (object) [
            'id' => 12,
            'email' => 'bob@example.com',
            'firstname' => 'Bob',
            'lastname' => 'Ray',
        ];

        $requestRepository->expects(self::once())
            ->method('findById')
            ->with(12)
            ->willReturn($request);
        $requestRepository->expects(self::once())
            ->method('update')
            ->with(12, ['status' => 'paid'])
            ->willReturn($updatedRequest);
        $campaignRepository->expects(self::once())
            ->method('findById')
            ->with(2)
            ->willReturn($campaign);

        $requestLogRepository->expects(self::once())
            ->method('insert')
            ->with(self::callback(function (array $log): bool {
                return $log['request_id'] === 12
                    && $log['status'] === 'paid'
                    && $log['changed_by'] === 99
                    && is_int($log['changed_at']);
            }))
            ->willReturn((object) []);

        $mailer->expects(self::once())
            ->method('sendMail')
            ->with(
                'bob@example.com',
                'wolf-membership:request-paid',
                self::callback(function (array $vars): bool {
                    return $vars['campaignName'] === 'Summer Camp'
                        && $vars['request_id'] === 12
                        && $vars['contactUrl'] === 'https://club.local/contact';
                })
            )
            ->willReturn(true);

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                'wolf_memberships_request_status_changed',
                self::callback(function ($event) use ($updatedRequest): bool {
                    return $event instanceof RequestStatusChangedEvent
                        && $event->getStatus() === 'paid'
                        && $event->getRequest() === $updatedRequest;
                })
            );

        $useCase = new MarkAsPaidRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 2,
            'request_id' => 12,
            'user_id' => 99,
        ]));
    }

    public function testExecuteThrowsWhenRequestStatusIsInvalid(): void
    {
        $requestRepository = $this->createMock(EntityRepositoryInterface::class);
        $requestRepository->method('findById')->willReturn((object) ['status' => 'pending']);

        $useCase = new MarkAsPaidRequestUseCase(
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
        $this->expectExceptionMessage('Only approved or rejected requests can be marked as paid.');

        $useCase->execute([
            'campaign_id' => 1,
            'request_id' => 20,
        ]);
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
            ->with('site_contact_url')
            ->willReturn('https://club.local/contact');

        $requestRepository->method('findById')->willReturn((object) ['id' => 15, 'status' => 'approved']);
        $campaignRepository->method('findById')->willReturn((object) ['title' => 'Summer Camp']);
        $updatedRequest = (object) [
            'id' => 15,
            'email' => 'bob@example.com',
            'firstname' => 'Bob',
            'lastname' => 'Ray',
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
                    return $event instanceof RequestStatusChangedEvent && $event->getStatus() === 'paid';
                })
            );

        $useCase = new MarkAsPaidRequestUseCase($entityManager, $mailer, $parameters, $dispatcher);
        self::assertSame([], $useCase->execute([
            'campaign_id' => 6,
            'request_id' => 15,
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
