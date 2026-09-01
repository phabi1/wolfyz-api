<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_5 implements MigrationInterface
{

    public function up()
    {
        $this->createPage('wolf_membership_result_page', 'Result', '<!-- wp:wolf-membership/result /-->');
    }

    public function down()
    {
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