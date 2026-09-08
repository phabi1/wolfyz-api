<?php

namespace App\HelloAsso\Sdk\Auth\Storage;

class MemoryStorage implements StorageInterface
{
    private $data = [];

    public function setOptions(array $options)
    {
        // No options needed for memory storage
    }

    public function getToken()
    {
        return $this->data;
    }

    public function setToken($token)
    {
        $this->data = $token;
    }
}