<?php

namespace App\HelloAsso\UseCase;

use App\Core\Entity\EntityManager;
use App\HelloAsso\Sdk\Client;
use App\Core\UseCase\UseCaseInterface;

class SyncRequestsUseCase implements UseCaseInterface
{
    private Client $client;
    private $subscriptionRepository;
    private $sessionRepository;

    public function __construct(Client $client, EntityManager $entityManager)
    {
        $this->client = $client;
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
        $this->sessionRepository = $entityManager->getRepository('wolf-memberships.session');
    }
    public function execute(array $params = [])
    {
        $orders = $this->client->getOrders()->list('Membership', 'adhesion-2026-2027');
        var_dump($orders);
    }
}