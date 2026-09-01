<?php

return [
    'wolf-helloasso.history' => [
        'table' => 'wolf_helloasso_history',
        'fields' => [
            'id' => ['type' => 'integer'],
            'event_id' => ['type' => 'string', 'required' => true, 'unique' => true],
            'event_type' => ['type' => 'string'],
            'event_data' => ['type' => 'json'],
            'created_at' => ['type' => 'datetime']
        ]
    ]
];
