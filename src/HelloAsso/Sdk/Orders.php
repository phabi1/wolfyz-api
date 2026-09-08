<?php

namespace App\HelloAsso\Sdk;

class Orders
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(string $formType, string $formSlug)
    {
        $organizationSlug = $organizationSlug ?? $this->client->getOrganizationSlug();
       return $this->client->request('GET', 'organizations/' . $organizationSlug . '/forms/' . $formType . '/' . $formSlug . '/orders');
    }
}