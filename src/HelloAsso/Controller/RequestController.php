<?php

namespace App\HelloAsso\Controller;

use App\Core\Mvc\Controller\ApiController;
use Symfony\Component\HttpFoundation\Request;

class RequestController extends ApiController
{
    public function syncAction(Request $request) {
        $res = $this->useCaseBus('wolf-helloasso.sync_requests', [

        ]);
        return ['success' => true];
    }
}