<?php

namespace App\HelloAsso\Sdk;

class Client
{
    private $auth;

    private $apiKey;
    private $apiSecret;

    private $organizationSlug = '';

    private $sandbox = false;

    private $services = [];

    public function __construct($apiKey, $apiSecret, $organizationSlug = '', $options = [])
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->organizationSlug = $organizationSlug;

        if (isset($options['sandbox'])) {
            $this->sandbox = $options['sandbox'];
        }

        $this->createAuth($options);
    }

    public function isSandbox()
    {
        return $this->sandbox;
    }

    public function setSandbox($sandbox)
    {
        $this->sandbox = $sandbox;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    public function getOrganizationSlug()
    {
        return $this->organizationSlug;
    }

    public function getBaseUri()
    {
        return $this->sandbox ? 'https://api.helloasso-sandbox.com/' : 'https://api.helloasso.com/';
    }

    public function getForms()
    {
        if (!isset($this->services['forms'])) {
            $this->services['forms'] = new Forms($this);
        }
        return $this->services['forms'];
    }

    public function getCheckout()
    {
        if (!isset($this->services['checkout'])) {
            $this->services['checkout'] = new Checkout($this);
        }
        return $this->services['checkout'];
    }

    public function request($method, $endpoint, $data = [])
    {
        $accessToken = $this->auth->getAccessToken();

        $baseUri = $this->getBaseUri();

        $client = new \GuzzleHttp\Client([
            'base_uri' => $baseUri . 'v5/',
        ]);

        try {
            $response = $client->request($method, $endpoint, [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();

                if ($statusCode === 403) {
                   echo 'Body: ' . $body;
                   echo 'Access Token: ' . $accessToken;
                }

                throw new \Exception("HTTP request failed with status code $statusCode: $body");
            } else {
                throw new \Exception("HTTP request failed: " . $e->getMessage());
            }
        }
    }

    private function createAuth(array $options = [])
    {
        $info = $options['auth_storage'] ?? 'wordpress';

        if (is_string($info)) {
            $type = $info;
            $options = [];
        } elseif (is_array($info) && isset($info['type'])) {
            $type = $info['type'];
            $options = $info['options'] ?? [];
        } else {
            throw new \InvalidArgumentException('Invalid auth_storage option');
        }

        switch ($type) {
            case 'wordpress':
                $storage = new Auth\Storage\WordpressStorage();
                break;
            default:
                throw new \InvalidArgumentException('Invalid auth_storage type: ' . $type);
        }

        $storage->setOptions($options);

        $this->auth = new Auth($this, $storage);
    }
}