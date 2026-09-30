<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepository;

class RequestEntityRepository extends EntityRepository implements RequestEntityRepositoryInterface
{
    /**
     * Counts the number of requests for each status provided within a specific campaign.
     *
     * @param int $campaignId The ID of the campaign to count requests for.
     * @param array $status An array of statuses to count requests for.
     * @return array An associative array where keys are statuses and values are the corresponding request counts.
     */
    public function countByStatus($campaignId, array $status): array
    {
        $query = $this->db->createQuery()
            ->from($this->definition['table'])
            ->select('status')
            ->select('COUNT(id)', 'request_count')
            ->where($this->db->expr()->eq('campaign_id', $campaignId))
            ->groupBy('status');

        if (!empty($status)) {
            $query->where($this->db->expr()->in('status', $status));
        }

        $results = array_reduce($this->db->rows($query), function ($carry, $item) {
            $carry[$item->status] = (int) $item->request_count;
            return $carry;
        }, []);

        // Transform the results into an associative array
        $countByStatus = [];
        foreach ($status as $s) {
            $countByStatus[$s] = isset($results[$s]) ? $results[$s] : 0;
        }

        return $countByStatus;
    }
}