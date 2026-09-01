<?php

namespace App\HelloAsso\Sdk\Auth\Storage;

class WordpressStorage implements StorageInterface
{

    public function setOptions(array $options)
    {
        // No options needed for WordpressStorage
    }
    public function getToken()
    {
        $data = get_option('wolf_helloasso_access_token', null);
        return $data ? json_decode($data, true) : null;
    }

    public function setToken($token)
    {
        $data = json_encode($token);
        update_option('wolf_helloasso_access_token', $data, false);
    }

}