<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepository;
use App\Core\Entity\EntityManager;
use App\Membership\Model\LicenseType;

class SubscriptionEntityRepository extends EntityRepository implements SubscriptionEntityRepositoryInterface
{
    private EntityManager $entityManager;

    /**
     * Data that only applies to a competition license
     */
    public const COMPETITION_ONLY_FIELDS = [
        'medical_certificate',
        'medical_certificate_date',
        'doctor',
        'identity_photo',
    ];

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Below this age (at the campaign start) a member is a minor
     */
    public const MAJORITY_AGE = 18;

    public function insert($obj): \stdClass
    {
        $obj = (array) $obj;
        $obj = $this->clearCompetitionOnlyFields($obj, $obj['license_type'] ?? null);
        $obj = $this->clearMinorOnlyFields($obj, $obj['member_id'] ?? null, $obj['campaign_id'] ?? null);
        return parent::insert($obj);
    }

    public function update($id, $obj): \stdClass
    {
        $obj = (array) $obj;
        $existing = null;
        $current = function (string $field) use ($id, &$existing) {
            $existing ??= $this->findById($id);
            return $existing->$field ?? null;
        };

        $obj = $this->clearCompetitionOnlyFields($obj, $obj['license_type'] ?? $current('license_type'));
        // Only needed when the exit authorization is written
        if (array_key_exists('agree_exit', $obj) && $obj['agree_exit'] !== null) {
            $obj = $this->clearMinorOnlyFields(
                $obj,
                $obj['member_id'] ?? $current('member_id'),
                $obj['campaign_id'] ?? $current('campaign_id')
            );
        }
        return parent::update($id, $obj);
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

        $member = $this->entityManager->getRepository('wolf-memberships.member')->findById($memberId);
        if (!$member || empty($member->birthdate)) {
            return $data;
        }

        $campaign = $campaignId
            ? $this->entityManager->getRepository('wolf-memberships.campaign')->findById($campaignId)
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

    /**
     * Medical certificate, its date, doctor and identity photo are only kept
     * for a competition license, whatever the caller (API, import, sync...).
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
     * Counts the number of requests for each status provided within a specific campaign.
     *
     * @param int $campaignId The ID of the campaign to count requests for.
     * @return array An associative array where keys are statuses and values are the corresponding request counts.
     */
    public function countByGender($campaignId): array
    {
        $query = $this->db->createQuery()
            ->from($this->definition['table'], 'a')
            ->innerJoin($this->entityManager->getEntityDefinition()->get('wolf-memberships.member')->getTable(), 'm', 'm.id = a.member_id')
            ->select('m.gender')
            ->select('COUNT(m.id)', 'gender_count')
            ->where($this->db->expr()->eq('a.campaign_id', $campaignId))
            ->groupBy('m.gender');

        // No need to filter by status as we are counting all genders

        $results = array_reduce($this->db->rows($query), function ($carry, $item) {
            $carry[$item->gender] = (int) $item->gender_count;
            return $carry;
        }, []);

        // Transform the results into an associative array
        $countByGender = [];
        foreach (['male', 'female', 'other'] as $gender) {
            $countByGender[$gender] = isset($results[$gender]) ? $results[$gender] : 0;
        }

        return $countByGender;
    }

    public function countByAge($campaignId, \DateTimeInterface $referenceDate): array
    {
        $query = $this->db->createQuery()
            ->from($this->definition['table'], 'a')
            ->innerJoin($this->entityManager->getEntityDefinition()->get('wolf-memberships.member')->getTable(), 'm', 'm.id = a.member_id')
            ->select('m.birthdate')
            ->select('COUNT(a.id)', 'member_count')
            ->where($this->db->expr()->eq('a.campaign_id', $campaignId))
            ->groupBy('m.birthdate');

        $reference = \DateTimeImmutable::createFromInterface($referenceDate)->setTime(0, 0);

        $ages = [];
        $unknown = 0;
        foreach ($this->db->rows($query) as $row) {
            $count = (int) $row->member_count;
            $birthdate = $row->birthdate ? \DateTimeImmutable::createFromFormat('!Y-m-d', substr($row->birthdate, 0, 10)) : false;
            // Missing, invalid or future birthdates cannot give an age
            if (!$birthdate || $birthdate > $reference) {
                $unknown += $count;
                continue;
            }
            $age = $birthdate->diff($reference)->y;
            $ages[$age] = ($ages[$age] ?? 0) + $count;
        }
        ksort($ages);

        return ['ages' => $ages, 'unknown' => $unknown];
    }

    public function countByLicenseType($campaignId, array $licenseTypes): array
    {
        $query = $this->db->createQuery()
            ->from($this->definition['table'], 'a')
            ->select('license_type')
            ->select('COUNT(a.id)', 'request_count')
            ->where($this->db->expr()->eq('a.campaign_id', $campaignId))
            ->groupBy('license_type');

        if (!empty($licenseTypes)) {
            $query->where($this->db->expr()->in('license_type', $licenseTypes));
        }

        $results = array_reduce($this->db->rows($query), function ($carry, $item) {
            $carry[$item->license_type] = (int) $item->request_count;
            return $carry;
        }, []);

        // Transform the results into an associative array
        $countByLicenseType = [];
        foreach ($licenseTypes as $s) {
            $countByLicenseType[$s] = isset($results[$s]) ? $results[$s] : 0;
        }

        return $countByLicenseType;
    }
}