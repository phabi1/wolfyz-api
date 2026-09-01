<?php

use App\Core\Entity\Definition\Field;

return [
    'wolf-billing.payment' => [
        'table' => 'wolf_billing_payment',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'type' => ['type' => Field::TYPE_STRING, 'enum' => ['credit', 'debit']],
            'payment_method' => ['type' => Field::TYPE_STRING],
            'amount' => ['type' => Field::TYPE_INTEGER],
            'currency' => ['type' => Field::TYPE_STRING],
            'payer_firstname' => ['type' => Field::TYPE_STRING],
            'payer_lastname' => ['type' => Field::TYPE_STRING],
            'payer_email' => ['type' => Field::TYPE_STRING],
            'payed_at' => ['type' => Field::TYPE_DATETIME],
            'external_id' => ['type' => Field::TYPE_STRING],
            'meta' => ['type' => Field::TYPE_JSON, 'nullable' => true],
            'created_at' => ['type' => Field::TYPE_DATETIME],
            'updated_at' => ['type' => Field::TYPE_DATETIME]
        ]
    ]
];