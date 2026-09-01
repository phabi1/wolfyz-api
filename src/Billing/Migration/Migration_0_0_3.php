<?php

namespace App\Billing\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_3 implements MigrationInterface
{

    public function up()
    {
        $this->addMetadataToPayment();
    }

    public function down()
    {
    }

    private function addMetadataToPayment()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wolf_billing_payment';
        $wpdb->query("ALTER TABLE $table ADD COLUMN metadata JSON NULL");
    }
}