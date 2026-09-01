<?php

namespace App\HelloAsso\Sdk;

class ClientFactory {
    public static function create()
    {
        $sandbox = get_option('wolf_helloasso_sandbox', false);
        $options = get_option('wolf_helloasso_credentials', []);
        $organizationSlug = get_option('wolf_helloasso_organization_slug', '');
        $apiKey = $options['api_key'] ?? '';
        $apiSecret = $options['api_secret'] ?? '';
        $client = new Client($apiKey, $apiSecret, $organizationSlug, ['sandbox' => $sandbox]);
        return $client;
    }
}