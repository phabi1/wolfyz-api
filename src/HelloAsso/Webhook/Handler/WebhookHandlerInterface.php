<?php

namespace App\HelloAsso\Webhook\Handler;

interface WebhookHandlerInterface
{
    public function handle(array $payload = []): void;
}