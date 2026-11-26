<?php

namespace App\Membership\Entity\Service;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityService;
use App\Membership\Model\LicenseType;

class SubscriptionEntityService extends EntityService
{
    /**
     * Data that only applies to a competition license
     */
    public const COMPETITION_ONLY_FIELDS = [
        'medical_certificate',
        'medical_certificate_date',
        'doctor',
        'identity_photo',
    ];

    /**
     * Below this age (at the campaign start) a member is a minor
     */
    public const MAJORITY_AGE = 18;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager);
        $this->setEntityName('wolf-memberships.subscription');
    }

    public function create($data)
    {
        $data = (array) $data;
        $data = $this->clearCompetitionOnlyFields($data, $data['license_type'] ?? null);
        $data = $this->clearMinorOnlyFields($data, $data['member_id'] ?? null, $data['campaign_id'] ?? null);

        return parent::create($data);
    }

    public function update($id, $data)
    {
        $data = (array) $data;
        $existing = null;
        $current = function (string $field) use ($id, &$existing) {
            $existing ??= $this->getRepository()->findById($id);
            return $existing->$field ?? null;
        };

        $data = $this->clearCompetitionOnlyFields($data, $data['license_type'] ?? $current('license_type'));
        // The member age is only needed when the exit authorization is written
        if (array_key_exists('agree_exit', $data) && $data['agree_exit'] !== null) {
            $data = $this->clearMinorOnlyFields(
                $data,
                $data['member_id'] ?? $current('member_id'),
                $data['campaign_id'] ?? $current('campaign_id')
            );
        }

        return parent::update($id, $data);
    }

    protected function validateData(array &$data, $entity = null): array
    {
        $errors = parent::validateData($data, $entity);

        if (
            $entity
            && isset($data['license_type'])
            && $data['license_type'] !== LicenseType::COMPETITION
        ) {
            $disciplineCount = $this->getEntityManager()
                ->getRepository('wolf-memberships.subscription_discipline')
                ->count(['subscription_id' => ['eq' => $entity->id]]);

            if ($disciplineCount > 1) {
                $this->addError(
                    'license_type',
                    'multiple_disciplines_not_allowed',
                    'A subscription with multiple disciplines must keep the competition license type',
                    $errors
                );
            }
        }

        return $errors;
    }

    /**
     * Medical certificate, its date, doctor and identity photo are only kept
     * for a competition license.
     */
    private function clearCompetitionOnlyFields(array $data, ?string $licenseType): array
    {
        if ($licenseType === null || $licenseType === LicenseType::COMPETITION) {
            return $data;
        }
        foreach (self::COMPETITION_ONLY_FIELDS as $field) {
            $data[$field] = null;
        }
        return $data;
    }

    /**
     * The exit authorization only concerns minors (at the campaign start, or
     * today without one). It is kept when the age cannot be computed.
     */
    private function clearMinorOnlyFields(array $data, $memberId, $campaignId): array
    {
        if (!array_key_exists('agree_exit', $data) || $data['agree_exit'] === null || !$memberId) {
            return $data;
        }

        $member = $this->getEntityManager()->getRepository('wolf-memberships.member')->findById($memberId);
        if (!$member || empty($member->birthdate)) {
            return $data;
        }

        $campaign = $campaignId
            ? $this->getEntityManager()->getRepository('wolf-memberships.campaign')->findById($campaignId)
            : null;
        $reference = !empty($campaign->start_date)
            ? (new \DateTimeImmutable())->setTimestamp((int) $campaign->start_date)
            : new \DateTimeImmutable();

        $birthdate = (new \DateTimeImmutable())->setTimestamp((int) $member->birthdate)->setTime(0, 0);
        $reference = $reference->setTime(0, 0);
        if ($birthdate > $reference) {
            return $data;
        }

        if ($birthdate->diff($reference)->y >= self::MAJORITY_AGE) {
            $data['agree_exit'] = null;
        }
        return $data;
    }
}
