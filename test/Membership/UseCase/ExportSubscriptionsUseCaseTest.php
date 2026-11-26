<?php

declare(strict_types=1);

namespace App\Test\Membership\UseCase;

use App\Core\Di\Locator;
use App\Core\Entity\EntityManager;
use App\Core\Entity\Exception\EntityNotFoundException;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Translation\Translator;
use App\Membership\UseCase\Subscription\ExportSubscriptionsUseCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class ExportSubscriptionsUseCaseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('APP_DIR')) {
            define('APP_DIR', dirname(__DIR__, 3));
        }
    }

    public function testCsvExportUsesTranslatedLabelsAsHeaders(): void
    {
        $campaignRepository = $this->createMock(EntityRepositoryInterface::class);
        $campaignRepository->method('findById')->with(5)->willReturn((object) ['settings' => (object) []]);
        $memberRepository = $this->createMock(EntityRepositoryInterface::class);
        $subscriptionRepository = $this->createMock(EntityRepositoryInterface::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturnMap([
            ['wolf-memberships.campaign', $campaignRepository],
            ['wolf-memberships.member', $memberRepository],
            ['wolf-memberships.subscription', $subscriptionRepository],
        ]);

        $helpers = $this->createMock(Locator::class);
        $helpers->method('get')->with('date')->willReturn(new \stdClass());

        $subscription = (object) [
            'id' => 1,
            'license_type' => 'hobby',
            'license_taken_at' => null,
            'subscribed_at' => null,
            'campaign_id' => 5,
            'member_id' => 7,
            'fields' => (object) [
                'identity_photo' => 'photo.jpg',
                'unknown_custom' => 'value',
            ],
        ];
        $member = (object) [
            'id' => 7,
            'firstname' => 'Bob',
            'lastname' => 'Ray',
            'birthdate' => null,
            'email' => 'bob@example.com',
            'license_number' => 'L123',
        ];

        $subscriptionRepository->method('find')->willReturn([$subscription]);
        $memberRepository->method('findByIds')->with([7])->willReturn([$member]);

        $useCase = new ExportSubscriptionsUseCase($entityManager, $helpers, new Translator());

        $result = $useCase->execute([
            'campaign_id' => 5,
            'format' => ExportSubscriptionsUseCase::FORMAT_CSV,
        ]);

        $handle = fopen($result['file_url'], 'r');
        $headers = fgetcsv($handle);
        $row = fgetcsv($handle);
        fclose($handle);
        unlink($result['file_url']);

        self::assertSame([
            'Identifiant',
            'Type de licence',
            'Licence prise le',
            'Date d\'inscription',
            'Identifiant campagne',
            'Identifiant adhérent',
            'Prénom',
            'Nom',
            'Date de naissance',
            'Email',
            'Numéro de licence',
            'Photo d\'identité',
            // Untranslated custom fields fall back to the raw field key
            'unknown_custom',
        ], $headers);

        self::assertSame('1', $row[0]);
        self::assertSame('Bob', $row[6]);
        self::assertSame('photo.jpg', $row[11]);
        self::assertSame('value', $row[12]);
    }

    public static function formats(): array
    {
        return [['csv'], ['xlsx']];
    }

    #[DataProvider('formats')]
    public function testExportsConfiguredFieldsAndSubscriptionValues(string $format): void
    {
        $this->expectOutputString('');
        $campaignRepository = $this->createMock(EntityRepositoryInterface::class);
        $campaignRepository->method('findById')->with(5)->willReturn((object) [
            'settings' => (object) ['custom_fields' => [
                (object) ['name' => 'size', 'label' => 'Taille'],
                (object) ['name' => 'agree', 'label' => 'Accord'],
                (object) ['name' => 'details', 'label' => 'Détails'],
                (object) ['name' => 'missing', 'label' => 'Absent'],
            ]],
        ]);
        $memberRepository = $this->createMock(EntityRepositoryInterface::class);
        $subscriptionRepository = $this->createMock(EntityRepositoryInterface::class);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturnMap([
            ['wolf-memberships.campaign', $campaignRepository],
            ['wolf-memberships.member', $memberRepository],
            ['wolf-memberships.subscription', $subscriptionRepository],
        ]);
        $helpers = $this->createMock(Locator::class);
        $helpers->method('get')->with('date')->willReturn(new \stdClass());

        $subscriptionRepository->expects(self::once())->method('find')
            ->with(['id' => ['not_in' => [9]], 'campaign_id' => ['eq' => 5]])->willReturn([
            (object) [
                'id' => 1, 'member_id' => 7, 'campaign_id' => 5, 'license_type' => 'hobby',
                'license_taken_at' => null, 'subscribed_at' => null,
                'fields' => (object) ['size' => 'M', 'agree' => false, 'details' => ['été', 'L']],
            ],
            (object) [
                'id' => 2, 'member_id' => 8, 'campaign_id' => 5, 'license_type' => 'hobby',
                'license_taken_at' => null, 'subscribed_at' => null,
                'fields' => (object) ['size' => 'XL', 'agree' => true],
            ],
        ]);
        $memberRepository->method('findByIds')->with([7, 8])->willReturn([
            (object) ['id' => 7, 'firstname' => 'Bob', 'lastname' => 'Ray', 'birthdate' => null, 'email' => null, 'license_number' => null],
            (object) ['id' => 8, 'firstname' => 'Ada', 'lastname' => 'Ray', 'birthdate' => null, 'email' => null, 'license_number' => null],
        ]);

        $result = (new ExportSubscriptionsUseCase($entityManager, $helpers, new Translator()))->execute([
            'campaign_id' => 5,
            'format' => $format,
            'ids' => [9],
            'reverse' => true,
        ]);

        try {
            if ($format === 'csv') {
                $handle = fopen($result['file_url'], 'r');
                $headers = fgetcsv($handle);
                $first = fgetcsv($handle);
                $second = fgetcsv($handle);
                fclose($handle);
            } else {
                $spreadsheet = IOFactory::load($result['file_url']);
                [$headers, $first, $second] = $spreadsheet->getActiveSheet()->toArray();
                $spreadsheet->disconnectWorksheets();
            }

            self::assertSame(['Taille', 'Accord', 'Détails', 'Absent'], array_slice($headers, 11));
            self::assertSame('M', $first[11]);
            self::assertEquals(0, $first[12]);
            self::assertSame('["été","L"]', $first[13]);
            self::assertTrue($first[14] === null || $first[14] === '');
            self::assertSame('XL', $second[11]);
            self::assertEquals(1, $second[12]);
            self::assertTrue($second[13] === null || $second[13] === '');
        } finally {
            unlink($result['file_url']);
        }
    }

    public function testMissingCampaignFails(): void
    {
        $campaignRepository = $this->createMock(EntityRepositoryInterface::class);
        $campaignRepository->method('findById')->willReturn(null);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturnMap([
            ['wolf-memberships.campaign', $campaignRepository],
            ['wolf-memberships.member', $this->createMock(EntityRepositoryInterface::class)],
            ['wolf-memberships.subscription', $this->createMock(EntityRepositoryInterface::class)],
        ]);
        $helpers = $this->createMock(Locator::class);
        $helpers->method('get')->willReturn(new \stdClass());

        $this->expectException(EntityNotFoundException::class);
        (new ExportSubscriptionsUseCase($entityManager, $helpers, new Translator()))->execute(['campaign_id' => 5]);
    }
}
