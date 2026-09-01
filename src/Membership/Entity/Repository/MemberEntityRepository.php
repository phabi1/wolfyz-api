<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepository;

class MemberEntityRepository extends EntityRepository implements MemberEntityRepositoryInterface
{
    public function existsHash(string $hash): int|null
    {
        $query = $this->db->createQuery();
        $query->select('id')
            ->from($this->definition['table'])
            ->where(
                $this->db->expr()->eq('hash', $hash)
            );
        $res = $this->db->value($query);
        return $res !== null ? (int) $res : null;
    }

    /**
     * Finds members with matching approximate lastname, firstname, and birthdate.
     * @param string $lastname
     * @param string $firstname
     * @param string $birthdate
     * @return array
     */
    public function findSuggestions(string $lastname, string $firstname, string $birthdate, int $minScore = 0): array
    {
        $query = $this->db->createQuery();
        $query->select('id')->select('firstname')->select('lastname')->select('birthdate')
            ->select('(
        (CASE WHEN LOWER(firstname) = LOWER("' . $firstname . '") THEN 30 ELSE 0 END) +
        (CASE WHEN SOUNDEX(firstname) = SOUNDEX("' . $firstname . '") THEN 20 ELSE 0 END) +
        (CASE WHEN LOWER(lastname) = LOWER("' . $lastname . '") THEN 30 ELSE 0 END)
    )', 'score');
        $query->from($this->definition['table']);

        $query->where(
            $this->db->expr()->or([
                $this->db->expr()->eq('SOUNDEX(firstname)', 'SOUNDEX("' . $firstname . '")'),
                $this->db->expr()->eq('SOUNDEX(lastname)', 'SOUNDEX("' . $lastname . '")'),
            ])
        );

        $query->orderBy('score', 'DESC')
            ->range(5);

        if ($minScore > 0) {
            $query->having(
                $this->db->expr()->gt(['score', $minScore], true)
            );
        }

        $res = $this->db->rows($query);

        return array_map(function ($row) {
            return [
                'id' => (int) $row->id,
                'firstname' => $row->firstname,
                'lastname' => $row->lastname,
                'birthdate' => $row->birthdate,
                'score' => (int) $row->score,
            ];
        }, $res);
    }
}