<?php

namespace App\HelloAsso\Sdk;

class Forms
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function item($formType, $formSlug, $organizationSlug = null)
    {
        $organizationSlug = $organizationSlug ?? $this->client->getOrganizationSlug();
        return $this->client->request('GET', 'organizations/' . $organizationSlug . '/forms/' . $formType . '/' . $formSlug . '/public', []);
    }
}