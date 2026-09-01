<?php

namespace App\HelloAsso\Webhook;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Core\Di\Locator;

class WebhookBus implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $locator;

    public function execute($event, array $payload = []): void
    {
        if (!$this->locator) {
            $this->locator = new Locator('wolf-helloasso.webhook_handler');
            $this->locator->setContainer($this->container);
        }

        $handler = $this->locator->get($event);

        $handler->handle($payload);
    }
}