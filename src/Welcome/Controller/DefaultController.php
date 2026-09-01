<?php

namespace App\Welcome\Controller;

use App\Core\Mvc\Controller\ApiController;

class DefaultController extends ApiController {
    public function indexAction()
    {
        return ['message' => 'Welcome to API!'];
    }
}