<?php

namespace App\Event\Token;

class TokenService
{
    /**
     * Generate a token for a given checkout ID.
     *
     * @param int $checkoutId The checkout ID.
     * @return string The generated token.
     */
    public function generateToken(int $checkoutId): string
    {
        return hash_hmac('sha256', (string) $checkoutId, wp_salt());
    }

    /**
     * Validate a token against a given checkout ID.
     *
     * @param int    $checkoutId The checkout ID.
     * @param string $token      The token to validate.
     * @return bool True if the token is valid, false otherwise.
     */
    public function validateToken(int $checkoutId, string $token): bool
    {
        return hash_equals($this->generateToken($checkoutId), $token);
    }
}