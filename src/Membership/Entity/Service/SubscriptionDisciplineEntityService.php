<?php

namespace App\Membership\Entity\Service;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityService;
use App\Membership\Model\LicenseType;

class SubscriptionDisciplineEntityService extends EntityService
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager);
        $this->setEntityName('wolf-memberships.subscription_discipline');
    }

    protected function validateData(array &$data, $entity = null): array
    {
        $errors = parent::validateData($data, $entity);
        if (!empty($errors)) {
            return $errors;
        }

        $subscriptionId = $data['subscription_id'] ?? ($entity->subscription_id ?? null);
        $disciplineId = $data['discipline_id'] ?? ($entity->discipline_id ?? null);

        if (!$subscriptionId) {
            return $errors;
        }

        $subscription = $this->getEntityManager()
            ->getRepository('wolf-memberships.subscription')
            ->findById($subscriptionId);

        if (!$subscription) {
            $this->addError('subscription_id', 'not_found', 'Subscription not found', $errors);
            return $errors;
        }

        $assignments = $this->getRepository()->find([
            'subscription_id' => ['eq' => $subscriptionId]
        ]);

        if ($entity) {
            $assignments = array_filter($assignments, function ($assignment) use ($entity) {
                return (int) $assignment->id !== (int) $entity->id;
            });
        }

        foreach ($assignments as $assignment) {
            if ($disciplineId !== null && (int) $assignment->discipline_id === (int) $disciplineId) {
                $this->addError('discipline_id', 'duplicate', 'Discipline is already assigned to this subscription', $errors);
                return $errors;
            }
        }

        if (count($assignments) >= 1 && $subscription->license_type !== LicenseType::COMPETITION) {
            $this->addError(
                'discipline_id',
                'multiple_disciplines_not_allowed',
                'A subscription can have multiple disciplines only if its license type is competition',
                $errors
            );
        }

        return $errors;
    }
}
