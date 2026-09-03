<?php

namespace App\Core\Mvc\View\Helper;

class Asset
{
    private $mediaServer;

    public function __construct($mediaServer = null)
    {
        $this->mediaServer = $mediaServer;
    }

    public function __invoke($path)
    {
        return ($this->mediaServer ?? '') . '/assets/' . ltrim($path, '/');
    }
}