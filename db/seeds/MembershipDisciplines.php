<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class MembershipDisciplines extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $disciplines = [
            ['title' => 'Roller Artistique'],
            ['title' => 'Roller Course'],
            ['title' => 'Roller Derby'],
            ['title' => 'Roller Freestyle Street/Rampe/Bowl'],
            ['title' => 'Roller Inline Freestyle'],
            ['title' => 'Rink Hockey'],
            ['title' => 'Roller Hockey'],
            ['title' => 'Randonnée roller et mobilités actives'],
            ['title' => 'Skateboard'],
            ['title' => 'Trottinette'],
            ['title' => 'Roller Soccer'],
            ['title' => 'Descente'],
            ['title' => 'Longue piste sur glace'],
            ['title' => 'Mountainboard'],
        ];

        $this->table('wolf_memberships_discipline')->insert($disciplines)->save();

    }
}
