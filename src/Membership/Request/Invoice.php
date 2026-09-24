<?php

namespace App\Membership\Request;

class Invoice
{
    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function url(\stdClass $request)
    {
        if (!$this->baseUrl) {
            throw new \Exception('Invoice page URL is not configured.');
        }
        $campaignId = $request->campaign_id;
        $requestId = $request->id;
        $token = $request->token;
        $path = '/membership/campaigns/' . $campaignId . '/requests/' . $requestId . '/invoice/download';
        return $this->baseUrl . $path . '?token=' . $token;
    }
}