<?php

declare(strict_types=1);

namespace App\Test\Membership\Entity\Service;

use App\Core\Entity\Definition\Definition;
use App\Core\Entity\Definition\Field;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Membership\Entity\Service\SubscriptionEntityService;
use App\Membership\Model\LicenseType;
use PHPUnit\Framework\TestCase;

final class SubscriptionEntityServiceTest extends TestCase
{
    private EntityRepositoryInterface $subscriptionRepository;

    private EntityRepositoryInterface $assignmentRepository;

    private SubscriptionEntityService $service;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(EntityRepositoryInterface::class);
        $this->assignmentRepository = $this->createMock(EntityRepositoryInterface::class);

        $this->subscriptionRepository->method('getDefinition')->willReturn(new Definition([
            'table' => 'wolf_memberships_subscription',
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'license_type' => ['type' => Field::TYPE_STRING, 'required' => true],
            ],
        ]));

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturnMap([
            ['wolf-memberships.subscription', $this->subscriptionRepository],
            ['wolf-memberships.subscription_discipline', $this->assignmentRepository],
        ]);

        $this->service = new class($entityManager) extends SubscriptionEntityService {
            public function validate(array $data, $entity = null): array
            {
                return $this->validateData($data, $entity);
            }
        };
    }

    public function testRejectsDowngradeToHobbyWithMultipleDisciplines(): void
    {
        $this->assignmentRepository->method('count')->willReturn(2);

        $entity = (object) ['id' => 1, 'license_type' => LicenseType::COMPETITION];
        $errors = $this->service->validate(['license_type' => LicenseType::HOBBY], $entity);

        self::assertArrayHasKey('license_type', $errors);
        self::assertArrayHasKey('multiple_disciplines_not_allowed', $errors['license_type']);
    }

    public function testAllowsDowngradeToHobbyWithAtMostOneDiscipline(): void
    {
        $this->assignmentRepository->method('count')->willReturn(1);

        $entity = (object) ['id' => 1, 'license_type' => LicenseType::COMPETITION];
        $errors = $this->service->validate(['license_type' => LicenseType::HOBBY], $entity);

        self::assertSame([], $errors);
    }
}
