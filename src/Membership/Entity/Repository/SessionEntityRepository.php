<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepository;

class SessionEntityRepository extends EntityRepository implements SessionEntityRepositoryInterface
{
    /**
     * Counts the number of sessions for each lesson ID provided.
     *
     * @param array $lessonIds An array of lesson IDs to count sessions for.
     * @return array An associative array where keys are lesson IDs and values are the corresponding session counts.
     */
    public function countByLessons(array $lessonIds): array
    {
        $query = $this->db->createQuery()
            ->from($this->definition['table'])
            ->select('lesson_id')
            ->select('COUNT(id)', 'session_count')
            ->where($this->db->expr()->in('lesson_id', $lessonIds))
            ->groupBy('lesson_id');

        $results = array_reduce($this->db->rows($query), function ($carry, $item) {
            $carry[$item->lesson_id] = (int) $item->session_count;
            return $carry;
        }, []);

        // Transform the results into an associative array
        $countByLesson = [];
        foreach ($lessonIds as $lessonId) {
            $countByLesson[$lessonId] = isset($results[$lessonId]) ? $results[$lessonId] : 0;
        }

        return $countByLesson;
    }
}