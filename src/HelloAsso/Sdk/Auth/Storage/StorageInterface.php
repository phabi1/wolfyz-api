<?php

namespace App\HelloAsso\Sdk\Auth\Storage;

interface StorageInterface
{
    public function setOptions(array $options);

    public function getToken();

    public function setToken($token);

}