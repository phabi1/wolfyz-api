<?php

namespace App\HelloAsso\Webhook\Handler;

use App\Core\Helper\StringHelper;

class ReceiveOrderHandler implements WebhookHandlerInterface
{
    private $stringHelper;

    public function __construct(
        StringHelper $stringHelper
    ) {
        $this->stringHelper = $stringHelper;
    }

    public function handle(array $payload = []): void
    {
        $externalId = $payload['metadata']['external_id'] ?? '';
        do_action('order_success', [
            'external_id' => $externalId
        ]);
    }
}