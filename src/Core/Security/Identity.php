<?php

namespace App\Core\Security;

/**
 * Caller of the current request: a user authenticated by an access token of
 * the auth server (with the permissions of their roles), or a trusted server
 * authenticated by the API key (no user, no permission).
 */
class Identity
{
    /**
     * @param string[] $permissions
     */
    public function __construct(
        private $id,
        private readonly array $permissions = [],
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
}
