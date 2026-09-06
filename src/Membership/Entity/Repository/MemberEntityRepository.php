<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepository;
use App\Core\Helper\StringHelper;

class MemberEntityRepository extends EntityRepository implements MemberEntityRepositoryInterface
{

    private StringHelper $stringHelper;

    public function __construct(StringHelper $stringHelper)
    {
        $this->stringHelper = $stringHelper;
    }

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
    public function findSuggestions(string $lastname, string $firstname, string $birthdate): array
    {
        $firstname = $this->stringHelper->slug($firstname);
        $lastname = $this->stringHelper->slug($lastname);
        $birthdate = $this->stringHelper->slug($birthdate);

        $query = $this->db->createQuery();
        $query->select('id')->select('firstname')->select('lastname')->select('birthdate');
        $query->from($this->definition['table']);

        $members = $this->db->rows($query);

        $recognizer = new \App\Membership\Member\Recognizer($this->stringHelper);
        $res = $recognizer->recognize($members, $firstname, $lastname);

        $suggestions = array_splice($res, 0, 5);

        return array_map(function ($row) {
            return [
                'id' => (int) $row->id,
                'firstname' => $row->firstname,
                'lastname' => $row->lastname,
                'birthdate' => $row->birthdate,
                'score' => (int) $row->score,
            ];
        }, $suggestions);
    }
}