<?php

namespace App\HelloAsso\Sdk\Model;

class Payer {
    public $first_name;
    public $last_name;
    public $email;

    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}