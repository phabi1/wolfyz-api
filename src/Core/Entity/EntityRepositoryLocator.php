<?php

namespace App\Core\Entity;

use App\Core\Db\Db;
use App\Core\Entity\EntityDefinition;
use App\Core\Di\Locator;

class EntityRepositoryLocator extends Locator
{
    private $db;
    private $entityDefinition;

    public function __construct()
    {
        parent::__construct('entity.repository');
    }

    public function get($id)
    {

        if ($this->has($id)) {
            $repository = parent::get($id);
        } else {
            $repository = new EntityRepository();
        }
        return $repository;
    }
}