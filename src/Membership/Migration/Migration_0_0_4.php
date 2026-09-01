<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_4 implements MigrationInterface
{

    public function up()
    {
        $this->createRequestLogTable();
    }

    public function down()
    {
    }

    private function createRequestLogTable()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_request_log';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") !== $tableName) {
            $charsetCollate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $tableName (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                request_id INT UNSIGNED NOT NULL,
                status VARCHAR(20) NOT NULL,
                params JSON DEFAULT NULL,
                changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                changed_by INT UNSIGNED DEFAULT NULL,
                PRIMARY KEY (id)
            ) $charsetCollate;";

            $wpdb->query($sql);

            // Add indexes for foreign keys
            $wpdb->query("ALTER TABLE `$tableName` ADD INDEX `request_id` (`request_id`)");

            // Add foreign key constraints
            $wpdb->query("ALTER TABLE `$tableName` ADD FOREIGN KEY (`request_id`) REFERENCES `{$wpdb->prefix}wolf_memberships_request`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT");
        }
    }
}