<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_7 implements MigrationInterface
{

    public function up()
    {
        $this->addWheels();
    }

    public function down()
    {
    }

    private function addWheels()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_wheel';

        $wheels = [
            [
                'title' => 'Jaune',
                'color' => '#FFFF00',
                'children' => [
                    [
                        'title' => 'Verte',
                        'color' => '#00FF00',
                        'children' => [
                            [
                                'title' => 'Bleue',
                                'color' => '#0000FF',
                                'children' => [
                                    [
                                        'title' => 'Rouge',
                                        'color' => '#FF0000',
                                        'children' => [
                                            [
                                                'title' => 'Noire',
                                                'color' => '#000000',
                                            ],
                                        ]
                                    ],
                                    [
                                        'title' => 'Rouge SkatePark',
                                        'color' => '#FF0000',
                                        'children' => [
                                            [
                                                'title' => 'Noire SkatePark',
                                                'color' => '#000000',
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
        ];

        $this->insertWheels($wheels, null, '/', $tableName);

    }

    private function insertWheels($wheels, $parent, $path, $tableName)
    {
        global $wpdb;

        foreach ($wheels as $wheel) {
            $wpdb->insert($tableName, [
                'title' => $wheel['title'],
                'color' => $wheel['color'],
                'parent_id' => $parent,
                'parent_path' => $path,
            ]);

            $id = $wpdb->insert_id;

            if (isset($wheel['children']) && !empty($wheel['children'])) {
                $this->insertWheels($wheel['children'], $id, $path . $id . '/', $tableName);
            }
        }
    }
}