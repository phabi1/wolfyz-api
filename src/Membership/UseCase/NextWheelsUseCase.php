<?php

namespace App\Membership\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\Db\Db;

class NextWheelsUseCase implements UseCaseInterface
{
    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function execute(array $params = []): array
    {
        $campaignId = $params['campaign_id'] ?? null;
        if ($campaignId === null) {
            throw new \InvalidArgumentException('Campaign ID is required.');
        }
        $sql = "WITH
members AS (
    	SELECT s.member_id AS id FROM wp_wolf_memberships_subscription AS s 
        WHERE s.campaign_id = {$campaignId}
    ),
assigned AS(
    SELECT DISTINCT
        wa.member_id,
        wa.wheel_id
    FROM
        wp_wolf_memberships_wheel_assignment AS wa
    INNER JOIN members AS m ON m.id = wa.member_id  
),
next_from_progress AS(
    SELECT
        a.member_id,
        w_child.id AS next_wheel_id
    FROM
        assigned a
    JOIN wp_wolf_memberships_wheel AS w_child
    ON
        w_child.parent_id = a.wheel_id
    LEFT JOIN assigned AS w_parent
    ON
        w_parent.wheel_id = w_child.id AND w_parent.member_id = a.member_id
    WHERE
        w_parent.wheel_id IS NULL
),
next_for_new_members AS(
    SELECT
        m.id,
        w.id AS next_wheel_id
    FROM
        members AS m
    JOIN wp_wolf_memberships_wheel AS w
    ON
        w.parent_id IS NULL
    WHERE NOT
        EXISTS(
        SELECT
            1
        FROM
            assigned AS a
        WHERE
            a.member_id = m.id
    )
),
combined AS(
    SELECT
        *
    FROM
        next_from_progress
    UNION ALL
SELECT
    *
FROM
    next_for_new_members
)
SELECT
    m.id AS member_id,
    m.firstname,
    m.lastname,
    w.id AS wheel_id,
    w.title AS wheel_title
FROM
    combined
JOIN wp_wolf_memberships_wheel AS w
ON
    w.id = combined.next_wheel_id
JOIN wp_wolf_memberships_member AS m
ON
    m.id = combined.member_id
ORDER BY
    member_id,
    next_wheel_id;";
        $res = $this->db->rows($sql);
        return array_map(function ($row) {
            return [
                'member_id' => (int)$row->member_id,
                'firstname' => $row->firstname,
                'lastname' => $row->lastname,
                'wheel_id' => (int)$row->wheel_id,
                'wheel_title' => $row->wheel_title,
            ];
        }, $res);
    }
}