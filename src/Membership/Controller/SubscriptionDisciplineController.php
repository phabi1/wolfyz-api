<?php

namespace App\Membership\Controller;

use App\Core\Mvc\Controller\EntityController;
use App\Membership\Entity\Service\SubscriptionDisciplineEntityService;

class SubscriptionDisciplineController extends EntityController
{
    protected $entityName = 'wolf-memberships.subscription_discipline';

    protected $usePagination = false;

    protected function buildFilters($request)
    {
        $filters = parent::buildFilters($request);
        $subscriptionId = $request->query->get('subscription_id');
        if ($subscriptionId) {
            $filters['subscription_id'] = ['eq' => $subscriptionId];
        }
        return $filters;
    }

    protected function buildEntityService()
    {
        return new SubscriptionDisciplineEntityService($this->getService('entity.manager'));
    }
}
