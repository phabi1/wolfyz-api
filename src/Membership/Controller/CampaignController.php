<?php

namespace App\Membership\Controller;

use Symfony\Component\HttpFoundation\Request;

use App\Core\Mvc\Controller\EntityController;

class CampaignController extends EntityController
{
    protected $entityName = 'wolf-memberships.campaign';

    public function updateCampaignSettingsAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $settings = $request->getPayload()->all();

        $this->useCaseBus('wolf-memberships.update_campaign_settings', [
            'campaign_id' => $campaignId,
            'settings' => $settings
        ]);

        return ['success' => true];
    }

    public function currentWheelsAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');

        if ($campaignId === null) {
            return ['items' => []];
        }

        $wheelsStr = $request->query->get('wheels', '') ?? null;
        $wheels = $wheelsStr ? explode(',', $wheelsStr) : [];

        $items = $this->useCaseBus('wolf-memberships.current_wheels', [
            'campaign_id' => $campaignId,
            'wheels' => $wheels
        ]);

        return ['items' => $items];
    }

    public function nextWheelsAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');

        if ($campaignId === null) {
            return ['items' => []];
        }

        $wheelsStr = $request->query->get('wheels', '');
        $wheels = $wheelsStr ? explode(',', $wheelsStr) : [];

        $items = $this->useCaseBus('wolf-memberships.next_wheels', [
            'campaign_id' => $campaignId,
            'wheels' => $wheels
        ]);

        return ['items' => $items];
    }
}