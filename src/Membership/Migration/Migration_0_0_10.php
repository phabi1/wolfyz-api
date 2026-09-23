<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_10 implements MigrationInterface
{

    public function up()
    {
        $this->addPricingBreakdownColumn();
    }

    public function down()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_request';

        $wpdb->query("ALTER TABLE $tableName DROP COLUMN pricing_breakdown");
    }

    private function addPricingBreakdownColumn()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_request';

        $wpdb->query("ALTER TABLE $tableName ADD COLUMN pricing_breakdown JSON NULL");
    }
}
