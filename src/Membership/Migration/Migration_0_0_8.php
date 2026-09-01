<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_8 implements MigrationInterface
{

    public function up()
    {
        $this->addDiscountAmountColumn();
    }

    public function down()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_request';

        $wpdb->query("ALTER TABLE $tableName DROP COLUMN discount_amount");
    }

    private function addDiscountAmountColumn()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_request';

        $wpdb->query("ALTER TABLE $tableName ADD COLUMN discount_amount INT DEFAULT 0");

    }
}