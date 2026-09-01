<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_3 implements MigrationInterface
{

    public function up()
    {
        $this->removeCheckoutColumnFromSubscriptionsTable();
        $this->createRequestTable();
    }

    public function down()
    {
    }

    private function removeCheckoutColumnFromSubscriptionsTable()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_subscription';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName) {
            $columnExists = $wpdb->get_results("SHOW COLUMNS FROM `$tableName` LIKE 'checkout_id'");

            if (!empty($columnExists)) {
                $wpdb->query("ALTER TABLE `$tableName` DROP COLUMN `checkout_id`");
            }
        }
    }

    private function createRequestTable()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_request';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") !== $tableName) {
            $charsetCollate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $tableName (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                firstname VARCHAR(255) NOT NULL,
                lastname VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                data JSON NOT NULL,
                token VARCHAR(255) NOT NULL,
                validated_at DATETIME DEFAULT NULL,
                validated_by INT UNSIGNED DEFAULT NULL,
                rejected_at DATETIME DEFAULT NULL,
                rejected_by INT UNSIGNED DEFAULT NULL,
                rejection_reason TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                campaign_id INT UNSIGNED,
                PRIMARY KEY (id)
            ) $charsetCollate;";

            $wpdb->query($sql);

            // Add indexes for foreign keys
            $wpdb->query("ALTER TABLE `$tableName` ADD INDEX `campaign_id` (`campaign_id`)");

            // Add foreign key constraints
            $wpdb->query("ALTER TABLE `$tableName` ADD FOREIGN KEY (`campaign_id`) REFERENCES `{$wpdb->prefix}wolf_memberships_campaign`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT");
        }
    }
}