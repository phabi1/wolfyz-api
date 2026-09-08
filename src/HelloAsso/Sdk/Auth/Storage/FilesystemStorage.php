<?php

namespace App\HelloAsso\Sdk\Auth\Storage;

class FilesystemStorage implements StorageInterface
{
    private $filePath = '';


    public function setOptions(array $options)
    {
        if (isset($options['path'])) {
            $this->filePath = $options['path'];
        }
    }

    public function getToken()
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $data = file_get_contents($this->filePath);
        return $data ? json_decode($data, true) : null;
    }

    public function setToken($token)
    {
        file_put_contents($this->filePath, json_encode($token));
    }
}