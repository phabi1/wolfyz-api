<?php

namespace App\Membership\Controller;

use App\Core\Mvc\Controller\EntityController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractCampaignController extends EntityController
{
    protected function buildFilters($request)
    {
        $filters = parent::buildFilters($request);
        $campaignId = $request->attributes->get('campaign_id');
        if ($campaignId) {
            $filters['campaign_id'] = ['eq' => $campaignId];
        }
        return $filters;
    }

    protected function prepareDataFromRequest(array $body, Request $request)
    {
        $data = parent::prepareDataFromRequest($body, $request);
        $campaignId = $request->attributes->get('campaign_id');
        if ($campaignId) {
            $data['campaign_id'] = (int) $campaignId;
        }
        return $data;
    }
}