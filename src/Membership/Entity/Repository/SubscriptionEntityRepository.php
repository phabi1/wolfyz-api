<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepository;
use App\Core\Entity\EntityManager;

class SubscriptionEntityRepository extends EntityRepository implements SubscriptionEntityRepositoryInterface
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
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