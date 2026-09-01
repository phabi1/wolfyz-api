<?php

namespace App\HelloAsso\Sdk;

class Auth
{
    /**
     * Summary of client
     * @var Client
     */
    private $client;

    /**
     * Summary of httpClient
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    private $storage;

    public function __construct(Client $client, \App\HelloAsso\Sdk\Auth\Storage\StorageInterface $storage)
    {
        $this->client = $client;
        $this->storage = $storage;

        $baseUri = $this->client->getBaseUri();

        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $baseUri,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function getAccessToken()
    {
        $token = $this->storage->getToken();
        if (!$token) {
            $token = $this->fetchAccessToken();
            if (!$token) {
                throw new \Exception('Unable to fetch access token from HelloAsso API');
            }
            $this->storage->setToken($token);
            $accessToken = $token['access_token'];
        } else {
            if (time() < $token['expires_at']) {
                $accessToken = $token['access_token'];
            } elseif (isset($token['refresh_token'])) {
                $token = $this->refreshAccessToken($token['refresh_token']);
                if ($token) {
                    $this->storage->setToken($token);
                    $accessToken = $token['access_token'];
                } else {
                    throw new \Exception('Unable to refresh access token from HelloAsso API');
                }
            } else {
                $token = $this->fetchAccessToken();
                if (!$token) {
                    throw new \Exception('Unable to fetch access token from HelloAsso API');
                }
                $this->storage->setToken($token);
                $accessToken = $token['access_token'];
            }
        }
        return $accessToken;
    }

    private function fetchAccessToken()
    {
        $response = $this->httpClient->request('POST', 'oauth2/token', [
            'form_params' => [
                'client_id' => $this->client->getApiKey(),
                'client_secret' => $this->client->getApiSecret(),
                'grant_type' => 'client_credentials',
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_type' => $data['token_type'],
                'expires_at' => time() + $data['expires_in'] - 60, // 60 seconds buffer
            ];
        }

        return null;
    }

    private function refreshAccessToken($refreshToken)
    {
        $response = $this->httpClient->request('POST', 'oauth2/token', [
            'form_params' => [
                'client_id' => $this->client->getApiKey(),
                'client_secret' => $this->client->getApiSecret(),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_type' => $data['token_type'],
                'expires_at' => time() + $data['expires_in'] - 60, // 60 seconds buffer
            ];
        }

        return null;
    }
}