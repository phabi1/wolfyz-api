<?php

namespace App\Billing\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_4 implements MigrationInterface
{

    public function up()
    {
        $this->addPayerToPayment();
    }

    public function down()
    {
    }

    private function addPayerToPayment()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wolf_billing_payment';
        $wpdb->query("ALTER TABLE $table ADD COLUMN payer_firstname VARCHAR(255) NULL");
        $wpdb->query("ALTER TABLE $table ADD COLUMN payer_lastname VARCHAR(255) NULL");
        $wpdb->query("ALTER TABLE $table ADD COLUMN payer_email VARCHAR(255) NULL");
    }
}