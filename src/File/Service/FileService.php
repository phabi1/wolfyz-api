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

    public function resolveAvailablePath(string $directory, string $originalFilename): string
    {
        $normalizedDirectory = trim($directory, '/');
        $safeName = basename($originalFilename);

        if ($safeName === '') {
            $safeName = uniqid('file_', true);
        }

        $pathInfo = pathinfo($safeName);
        $name = $pathInfo['filename'] ?? 'file';
        $extension = isset($pathInfo['extension']) && $pathInfo['extension'] !== ''
            ? '.' . $pathInfo['extension']
            : '';

        $candidate = $name . $extension;
        $index = 1;

        while ($this->fileExists($normalizedDirectory . '/' . $candidate)) {
            $candidate = sprintf('%s (%d)%s', $name, $index, $extension);
            $index++;
        }

        return $normalizedDirectory . '/' . $candidate;
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