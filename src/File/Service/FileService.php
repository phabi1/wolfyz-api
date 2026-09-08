<?php

namespace App\File\Service;

class FileService
{
    private $storageDirectory;

    public function __construct()
    {
        $this->storageDirectory = APP_DIR . '/files';
    }

    public function create(string $uri)
    {

    }

    public function remove(string $uri)
    {
    }

    public function prepareDirectory(string $directory)
    {
        $path = $this->storageDirectory . DIRECTORY_SEPARATOR . $directory;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public function fileExists(string $uri): bool
    {
        $path = $this->getPath($uri);
        return file_exists($path);
    }

    public function unlink(string $uri)
    {
        $path = $this->getPath($uri);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function getPath(string $uri): string
    {
        return $this->storageDirectory . DIRECTORY_SEPARATOR . $uri;
    }
}