<?php

namespace App\Core\Security;

/**
 * Caller of the current request, authenticated by an access token of the auth
 * server: a user (permissions of their roles) or an OAuth client acting on
 * its own behalf, e.g. the WordPress site (permissions of the client's roles).
 */
class Identity
{
    /**
     * @param string[] $permissions
     * @param bool $user false for an OAuth client acting on its own behalf
     * @param string|null $email email of the user ("email" scope)
     */
    public function __construct(
        private $id,
        private readonly array $permissions = [],
        private readonly bool $user = true,
        private readonly ?string $email = null,
        private readonly bool $emailVerified = false,
    ) {
    }

    public function getId()
    {
        return $this->id;
    }

    /** @return string[] */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /** True when the identity has at least one of the given permissions. */
    public function hasPermission(string ...$anyOf): bool
    {
        return array_intersect($anyOf, $this->permissions) !== [];
    }

    /** False for an OAuth client acting on its own behalf (client_credentials). */
    public function isUser(): bool
    {
        return $this->user;
    }

    /** Email of the user, only when verified by the auth server. */
    public function getVerifiedEmail(): ?string
    {
        return $this->emailVerified ? $this->email : null;
    }
}
