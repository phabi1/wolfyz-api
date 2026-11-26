<?php

declare(strict_types=1);

namespace App\Test\Membership\Entity\Service;

use App\Core\Entity\Definition\Definition;
use App\Core\Entity\Definition\Field;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Membership\Entity\Service\SubscriptionDisciplineEntityService;
use App\Membership\Model\LicenseType;
use PHPUnit\Framework\TestCase;

final class SubscriptionDisciplineEntityServiceTest extends TestCase
{
    private EntityRepositoryInterface $subscriptionRepository;

    private EntityRepositoryInterface $assignmentRepository;

    private SubscriptionDisciplineEntityService $service;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(EntityRepositoryInterface::class);
        $this->assignmentRepository = $this->createMock(EntityRepositoryInterface::class);

        $this->assignmentRepository->method('getDefinition')->willReturn(new Definition([
            'table' => 'wolf_memberships_subscription_discipline',
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'subscription_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'discipline_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            ],
        ]));

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturnMap([
            ['wolf-memberships.subscription', $this->subscriptionRepository],
            ['wolf-memberships.subscription_discipline', $this->assignmentRepository],
        ]);

        $this->service = new class($entityManager) extends SubscriptionDisciplineEntityService {
            public function validate(array $data, $entity = null): array
            {
                return $this->validateData($data, $entity);
            }
        };
    }

    public function testAllowsFirstDisciplineForHobbySubscription(): void
    {
        $this->subscriptionRepository->method('findById')->with(1)
            ->willReturn((object) ['id' => 1, 'license_type' => LicenseType::HOBBY]);
        $this->assignmentRepository->method('find')->willReturn([]);

        $errors = $this->service->validate(['subscription_id' => 1, 'discipline_id' => 5]);

        self::assertSame([], $errors);
    }

    public function testRejectsSecondDisciplineForHobbySubscription(): void
    {
        $this->subscriptionRepository->method('findById')->with(1)
            ->willReturn((object) ['id' => 1, 'license_type' => LicenseType::HOBBY]);
        $this->assignmentRepository->method('find')->willReturn([
            (object) ['id' => 10, 'subscription_id' => 1, 'discipline_id' => 4],
        ]);

        $errors = $this->service->validate(['subscription_id' => 1, 'discipline_id' => 5]);

        self::assertArrayHasKey('discipline_id', $errors);
        self::assertArrayHasKey('multiple_disciplines_not_allowed', $errors['discipline_id']);
    }

    public function testAllowsMultipleDisciplinesForCompetitionSubscription(): void
    {
        $this->subscriptionRepository->method('findById')->with(1)
            ->willReturn((object) ['id' => 1, 'license_type' => LicenseType::COMPETITION]);
        $this->assignmentRepository->method('find')->willReturn([
            (object) ['id' => 10, 'subscription_id' => 1, 'discipline_id' => 4],
            (object) ['id' => 11, 'subscription_id' => 1, 'discipline_id' => 6],
        ]);

        $errors = $this->service->validate(['subscription_id' => 1, 'discipline_id' => 5]);

        self::assertSame([], $errors);
    }

    public function testRejectsDuplicateDiscipline(): void
    {
        $this->subscriptionRepository->method('findById')->with(1)
            ->willReturn((object) ['id' => 1, 'license_type' => LicenseType::COMPETITION]);
        $this->assignmentRepository->method('find')->willReturn([
            (object) ['id' => 10, 'subscription_id' => 1, 'discipline_id' => 5],
        ]);

        $errors = $this->service->validate(['subscription_id' => 1, 'discipline_id' => 5]);

        self::assertArrayHasKey('discipline_id', $errors);
        self::assertArrayHasKey('duplicate', $errors['discipline_id']);
    }

    public function testRejectsUnknownSubscription(): void
    {
        $this->subscriptionRepository->method('findById')->with(99)->willReturn(null);

        $errors = $this->service->validate(['subscription_id' => 99, 'discipline_id' => 5]);

        self::assertArrayHasKey('subscription_id', $errors);
        self::assertArrayHasKey('not_found', $errors['subscription_id']);
    }

    public function testIgnoresCurrentAssignmentWhenUpdating(): void
    {
        $this->subscriptionRepository->method('findById')->with(1)
            ->willReturn((object) ['id' => 1, 'license_type' => LicenseType::HOBBY]);
        $this->assignmentRepository->method('find')->willReturn([
            (object) ['id' => 10, 'subscription_id' => 1, 'discipline_id' => 4],
        ]);

        $entity = (object) ['id' => 10, 'subscription_id' => 1, 'discipline_id' => 4];
        $errors = $this->service->validate(['discipline_id' => 5], $entity);

        self::assertSame([], $errors);
    }
}
