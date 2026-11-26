<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * agree_image was created NOT NULL without default, which makes every insert
 * that does not provide it fail (request conversion, CSV import, ...).
 */
final class DefaultAgreeImageOfSubscription extends AbstractMigration
{
    public function up(): void
    {
        $this->table('wolf_memberships_subscription')
            ->changeColumn('agree_image', 'boolean', ['null' => false, 'default' => false])
            ->update();
    }

    public function down(): void
    {
        $this->table('wolf_memberships_subscription')
            ->changeColumn('agree_image', 'boolean', ['null' => false])
            ->update();
    }
}
