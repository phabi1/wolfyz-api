<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_2 implements MigrationInterface
{

    public function up()
    {
        $this->setupMemberships();
    }

    public function down()
    {
    }

    private function setupMemberships()
    {
        $this->createPage(
            'wolf_membership_registration_page',
            __('Membership Registration', 'wolf-membership'),
            '<!-- wp:wolf-membership/registration-form /-->'
        );

        $this->createPage(
            'wolf_membership_pay_page',
            __('Membership Pay', 'wolf-membership'),
            '<!-- wp:wolf-membership/pay /-->'
        );
    }

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
        ]);

        update_option($name, $pageId, false);
    }
}