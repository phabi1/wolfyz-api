<?php

namespace App\Membership\Entity\Service;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityService;
use App\Membership\Helper\MemberHelper;

class MemberEntityService extends EntityService
{
    private $memberHelper;

    public function __construct(EntityManager $entityManager, MemberHelper $memberHelper)
    {
        parent::__construct($entityManager);
        $this->memberHelper = $memberHelper;

    }

    public function create($data)
    {
        $firstname = $data['firstname'] ?? null;
        $lastname = $data['lastname'] ?? null;
        $birthdate = $data['birthdate'] ?? null;

        $hash = $this->memberHelper->generateHash($firstname, $lastname, $birthdate);
        $data['hash'] = $hash;
        try {
            return parent::create($data);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new \Exception('Member already exists.');
            }
            throw $e;
        }
    }

    public function update($id, $data)
    {
        if (isset($data['firstname']) || isset($data['lastname']) || isset($data['birthdate'])) {
            $entity = parent::item($id);
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
}