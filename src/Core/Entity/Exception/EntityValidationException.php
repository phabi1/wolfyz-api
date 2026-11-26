<?php

namespace App\Core\Entity\Exception;

class EntityValidationException extends EntityException
{

    private $errors = [];

    public function __construct(string $entityName, array $errors = [])
    {
        parent::__construct(
            $entityName,
            'Enity has errors'
        );
        $this->errors = $errors;
    }

    public function getErrors(): array {
        return $this->errors;
    }
}