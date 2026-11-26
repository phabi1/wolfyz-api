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