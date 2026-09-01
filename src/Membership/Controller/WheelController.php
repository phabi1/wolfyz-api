<?php

namespace App\Membership\Controller;

use App\Core\Mvc\Controller\EntityController;

class WheelController extends EntityController
{
    protected $entityName = 'wolf-memberships.wheel';

    protected $usePagination = false;
}