<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * wolf_watchdog_log: log table written by App\Core\Watchdog\Storage\DbStorage.
 * Without it, the first logged error is fatal.
 *
 * The auth server logs into its own auth_watchdog_log table. The table may
 * already exist (it used to be created by hand): it is only created when
 * missing, and the rollback keeps it so as not to delete existing logs.
 */
final class CreateWatchdogLog extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS wolf_watchdog_log (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                data JSON NOT NULL,
                level VARCHAR(16) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX created_at (created_at),
                INDEX level (level)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
    }

    public function down(): void
    {
        // May predate this migration: intentionally kept.
    }
}
