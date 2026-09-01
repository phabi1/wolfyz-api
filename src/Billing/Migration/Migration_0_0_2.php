<?php

namespace App\Billing\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_2 implements MigrationInterface
{

    public function up()
    {
    $this->setupCheck();
    $this->setupBankTransfer();
    $this->setupPaymentResult();
    }

    public function down()
    {
    }

    private function setupCheck()
    {
        $this->createPage(
            'wolf_billing_check_instruction_page',
            __('Check instructions', 'wolf-billing'),
            '<!-- wp:wolf-billing/pay-check /-->'
        );

        if (false === get_option('wolf_billing_check_info', false)) {
            update_option(
                'wolf_billing_check_info',
                wp_json_encode([
                    'reference' => true,
                    'order' => '',
                ]),
                false
            );
        }
    }

    private function setupBankTransfer()
    {
        $this->createPage(
            'wolf_billing_bank_transfer_instruction_page',
            __('Bank transfer instructions', 'wolf-billing'),
            '<!-- wp:wolf-billing/pay-bank-transfer /-->'
        );

        if (false === get_option('wolf_billing_bank_transfer_info', false)) {
            update_option(
                'wolf_billing_bank_transfer_info',
                wp_json_encode([
                    'reference' => true,
                    'order' => '',
                ]),
                false
            );
        }
    }

    private function setupPaymentResult()
    {
        
        $this->createPage(
            'wolf_billing_payment_result_page',
            __('Payment result', 'wolf-billing'),
            '<!-- wp:wolf-billing/payment-result /-->'
        );
    }

    /**
     * Summary of createPage
     * @param string $name
     * @param string $title
     * @param string $content
     * @return void
     */
    private function createPage(string $name, string $title, string $content)
    {
        $pageId = (int) get_option($name, 0);

        if ($pageId > 0 && get_post($pageId)) {
            return;
        }

        $pageId = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ]);

        if (!is_wp_error($pageId) && $pageId) {
            update_option($name, $pageId, false);
        }
    }
}