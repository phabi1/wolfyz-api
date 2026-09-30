<?php

namespace App\Core\Entity;

use App\Core\Db\Db;
use App\Core\Entity\Definition\Definition;

interface EntityRepositoryInterface
{
    public function setDb(Db $db);
    public function setDefinition(Definition $definition);
    public function getDefinition(): Definition;

    public function findById($id, array $options = []): \stdClass|null;

    public function findByIds(array $ids, array $options = []): array;

    public function find(array $filters = [], array $options = []): array;

    public function exists(array $filters = []): bool;

    public function count(array $filters = []): int;

    public function findOne(array $filters = [], array $options = []): \stdClass|null;

    public function insert($data): \stdClass;

    public function update($id, $data): \stdClass;

    public function delete($id);

    public function deleteBy($filters = []);
}