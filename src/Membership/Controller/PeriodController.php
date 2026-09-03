<?php

namespace App\Membership\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PeriodController extends AbstractCampaignController
{
    protected $entityName = 'wolf-memberships.period';

    public function printAction(Request $request)
    {
        $periodId = $request->attributes->get('period_id');
        if (!$periodId) {
            return new JsonResponse(['error' => 'period_id_required', 'message' => 'Period ID parameter is required'], 400);
        }

        $log = $this->useCaseBus('wolf-memberships.print_period', [
            'id' => $periodId
        ]);

        if (isset($log['error'])) {
            return new JsonResponse(['error' => 'print_failed', 'message' => $log['error']], 500);
        }

        $res = new Response($log['pdf']);
        $res->headers->set('Content-Type', 'application/pdf');
        $res->headers->set('Content-Disposition', 'attachment; filename="period_' . $periodId . '.pdf"');
        $res->headers->set('Content-Length', strlen($log['pdf']));
        $res->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');
        $res->headers->set('Expires', '0');
        $res->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
        return $res;
    }
}