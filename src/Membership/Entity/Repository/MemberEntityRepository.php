<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepository;
use App\Core\Helper\StringHelper;
use App\Membership\Helper\MemberHelper;

class MemberEntityRepository extends EntityRepository implements MemberEntityRepositoryInterface
{

    private StringHelper $stringHelper;
    private MemberHelper $memberHelper;

    public function __construct(StringHelper $stringHelper, MemberHelper $memberHelper)
    {
        $this->stringHelper = $stringHelper;
        $this->memberHelper = $memberHelper;
    }

    public function insert($data): \stdClass
    {
        $firstname = $data['firstname'] ?? null;
        $lastname = $data['lastname'] ?? null;
        $birthdate = $data['birthdate'] ?? null;

        $hash = $this->memberHelper->generateHash($firstname, $lastname, $birthdate);
        $data['hash'] = $hash;
        return parent::insert($data);
    }


    public function update($id, $data): \stdClass
    {
        if (isset($data['firstname']) || isset($data['lastname']) || isset($data['birthdate'])) {
            $entity = $this->findById($id);
            if (!$entity) {
                throw new \Exception("Member with ID {$id} not found.");
            }
            $firstname = $data['firstname'] ?? $entity->firstname;
            $lastname = $data['lastname'] ?? $entity->lastname;
            $birthdate = $data['birthdate'] ?? $entity->birthdate;

            $hash = $this->memberHelper->generateHash($firstname, $lastname, $birthdate);
            $data['hash'] = $hash;
        }

        try {
            return parent::update($id, $data);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new \Exception('Member already exists.');
            }
            throw $e;
        }
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