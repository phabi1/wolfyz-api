<?php

namespace App\Membership\UseCase;

use App\Core\Db\Db;
use App\Core\UseCase\UseCaseInterface;

class CurrentWheelsUseCase implements UseCaseInterface
{
    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function execute(array $params = [])
    {
        $campaignId = $params['campaign_id'] ?? null;

        $sql = "WITH
    members AS(
    SELECT
        s.member_id AS id
    FROM
        wp_wolf_memberships_subscription AS s
    WHERE
        s.campaign_id = {$campaignId}
),
assigned AS(
    SELECT DISTINCT
        wa.member_id,
        wa.wheel_id
    FROM
        wp_wolf_memberships_wheel_assignment AS wa
    INNER JOIN members AS m
    ON
        m.id = wa.member_id
),
latest_per_branch AS(
    SELECT
        a.member_id,
        a.wheel_id
    FROM
        assigned a
    WHERE NOT
        EXISTS(
        SELECT
            1
        FROM
            wp_wolf_memberships_wheel AS w_child
        JOIN assigned AS a2
        ON
            a2.wheel_id = w_child.id AND a2.member_id = a.member_id
        WHERE
            w_child.parent_id = a.wheel_id
    )
)
SELECT
    w.id AS wheel_id,
    w.title AS wheel_title,
FROM
    latest_per_branch AS l
JOIN wp_wolf_memberships_wheel AS w
ON
    w.id = l.wheel_id
GROUP BY
    w.id;";

        $items = $this->db->rows($sql);

        return array_map(function ($row) {
            return [
                'wheel_id' => (int) $row->wheel_id,
                'wheel_title' => $row->wheel_title,
                'count' => (int) $row->count,
            ];
        }, $items);
    }
}