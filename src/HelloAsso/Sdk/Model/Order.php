<?php

namespace App\HelloAsso\Sdk\Model;

use App\HelloAsso\Sdk\Model\Payer;

class Order
{
    public $amount_cents;
    public $item_name;
    public $terms = [];
    public $back_url;
    public $error_url;
    public $return_url;
    public Payer $payer;
    public $metadata;

    public function __construct(array $options = [])
    {
        $this->payer = new Payer();
        foreach ($options as $key => $value) {
            if ($key === 'payer' && is_array($value)) {
                $this->payer = new Payer($value);
            } else {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

}