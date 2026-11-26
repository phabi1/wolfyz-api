<?php

namespace App\Membership\Entity\Service;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityService;
use App\Membership\Model\LicenseType;

class SubscriptionEntityService extends EntityService
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager);
        $this->setEntityName('wolf-memberships.subscription');
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
}
