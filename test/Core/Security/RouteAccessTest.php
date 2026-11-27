<?php

declare(strict_types=1);

namespace App\Test\Core\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Access control declared on the routes ("auth" and "permission" keys, see
 * App\Core\Security\Firewall).
 */
final class RouteAccessTest extends TestCase
{
    private static function routes(): array
    {
        return require __DIR__ . '/../../../config/routes.php';
    }

    /** @return array<string, array{0: string, 1: array}> */
    public static function allRoutes(): array
    {
        $routes = [];
        foreach (self::routes() as $name => $route) {
            $routes[$name] = [$name, $route];
        }

        return $routes;
    }

    /** A "jwt" route without permission would be denied to every user of the club. */
    #[DataProvider('allRoutes')]
    public function testEveryUserRouteDeclaresAPermission(string $name, array $route): void
    {
        $auth = (array) ($route['auth'] ?? ['jwt']);

        self::assertNotSame([], $auth);
        self::assertSame([], array_diff($auth, ['jwt', 'api-key', 'public']), "Route \"$name\": unknown strategy.");
        if (in_array('jwt', $auth, true)) {
            self::assertNotEmpty($route['permission'] ?? [], "Route \"$name\" declares no permission.");
        }
    }

    public static function expectedAccess(): array
    {
        $anyView = ['membership.campaign.view', 'membership.request.view', 'membership.subscription.view'];

        return [
            'read uses the view permission' => ['membership-subscription-items', ['jwt'], ['membership.subscription.view']],
            'write uses the edit permission' => ['membership-subscription-update', ['jwt'], ['membership.subscription.edit']],
            'request action' => ['membership-request-approve', ['jwt'], ['membership.request.edit']],
            'campaign read by every membership page' => ['membership-campaign-item', ['jwt'], $anyView],
            'campaign settings' => ['membership-campaign-settings-update', ['jwt'], ['membership.campaign.edit']],
            'billing' => ['billing-payment-create', ['jwt'], ['billing.edit']],
            'event' => ['event-event-items', ['jwt'], ['event.view']],
            'WordPress only' => ['membership-register', ['api-key'], []],
            'WordPress and club' => ['membership-file-upload-post', ['jwt', 'api-key'], ['membership.subscription.edit']],
            'public' => ['hello-asso-webhooks-handle', ['public'], []],
        ];
    }

    #[DataProvider('expectedAccess')]
    public function testAccess(string $name, array $auth, array $permission): void
    {
        $route = self::routes()[$name];

        self::assertSame($auth, (array) ($route['auth'] ?? ['jwt']));
        self::assertSame($permission, (array) ($route['permission'] ?? []));
    }
}
