<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Links an event registration (checkout) to the account of the person who
 * made it (auth server user id, "sub" of the access token) when they were
 * signed in on the website: the "My registrations" block lists them.
 */
final class AddUserToEventCheckout extends AbstractMigration
{
    public function change(): void
    {
        $this->table('wolf_events_checkout')
            ->addColumn('user_id', 'string', ['limit' => 64, 'null' => true, 'after' => 'event_id'])
            ->addIndex(['user_id'], ['name' => 'checkout_user'])
            ->update();
    }
}
