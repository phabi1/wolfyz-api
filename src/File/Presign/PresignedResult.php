<?php

namespace App\File\Presign;

class PresignedResult
{

    const ERROR_EXPIRED = 'expired';
    const ERROR_INVALID_SIGNATURE = 'invalid_signature';

    private static $_errors = [
        self::ERROR_INVALID_SIGNATURE,
        self::ERROR_EXPIRED
    ];

    private $valid = true;

    private ?string $error = null;

    public function __construct(bool $valid, ?string $error = null)
    {
        $this->valid = $valid;
        $this->setError($error);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    private function setError(?string $error)
    {
        if ($error !== null && !\in_array($error, self::$_errors)) {
            throw new \InvalidArgumentException('The error must be values (' . implode(',', self::$_errors) . ')');
        }
        $this->error = $error;
    }
}