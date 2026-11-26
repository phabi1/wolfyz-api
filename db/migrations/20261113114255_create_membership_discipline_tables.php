<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMembershipDisciplineTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // subscription.id is referenced by other foreign keys (contacts...) and
        // cannot be altered: the pivot column follows its signedness instead
        $subscriptionIdSigned = true;
        foreach ($this->table('wolf_memberships_subscription')->getColumns() as $column) {
            if ($column->getName() === 'id') {
                $subscriptionIdSigned = $column->isSigned();
                break;
            }
        }

        $this->table('wolf_memberships_discipline')
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('description', 'string', ['limit' => 255])
            ->create();

        $this->table('wolf_memberships_subscription_discipline')
            ->addColumn('subscription_id', 'integer', ['null' => false, 'signed' => $subscriptionIdSigned])
            ->addColumn('discipline_id', 'integer', ['null' => false, 'signed' => false])
            ->addIndex(['subscription_id', 'discipline_id'], ['unique' => true])
            ->addForeignKey('subscription_id', 'wolf_memberships_subscription', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('discipline_id', 'wolf_memberships_discipline', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
