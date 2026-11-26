<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use function PHPUnit\Framework\isBool;

final class MigrateExtractFieldsOfSubscription extends AbstractMigration
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
    public function up(): void
    {
        $this->createMissingColumns();
        $this->migrateData();
    }

    protected function createMissingColumns(): void
    {
        $table = $this->table('wolf_memberships_subscription');
        if (!$table->hasColumn('medical_certificate')) {
            $table->addColumn(
                'medical_certificate',
                'string',
                [
                    'limit' => 255,
                    'null' => true,
                ]
            );
        }
        if (!$table->hasColumn('medical_certificate_date')) {
            $table->addColumn(
                'medical_certificate_date',
                'date',
                [
                    'null' => true,
                ]
            );
        }
        if (!$table->hasColumn('doctor')) {
            $table->addColumn(
                'doctor',
                'string',
                [
                    'limit' => 64,
                    'null' => true,
                ]
            );
        }
        if (!$table->hasColumn('medical_form')) {
            $table->addColumn(
                'medical_form',
                'string',
                [
                    'limit' => 255,
                    'null' => true,
                ]
            );
        }
        if (!$table->hasColumn('identity_photo')) {
            $table->addColumn(
                'identity_photo',
                'string',
                [
                    'limit' => 255,
                    'null' => true,
                ]
            );
        }
        if (!$table->hasColumn('agree_exit')) {
            $table->addColumn(
                'agree_exit',
                'boolean',
                [
                    'null' => true,
                ]
            );
        }
        if (!$table->hasColumn('agree_image')) {
            $table->addColumn(
                'agree_image',
                'boolean',
                [
                    'null' => false,
                ]
            );
        }
        $table->update();
    }

    protected function migrateData(): void
    {
        $rows = $this->fetchAll('SELECT * FROM wolf_memberships_subscription');
        foreach ($rows as $row) {
            $custom_fields = json_decode($row['fields'] ?? '{}', true);

            $new_fields = $custom_fields;
            // remove extracted fields from new_fields
            unset($new_fields['medical_certificate'], $new_fields['medical_certificate_date'], $new_fields['doctor'], $new_fields['medical_form'], $new_fields['identity_photo'], $new_fields['agree_exit'], $new_fields['agree_image']);

            $builder = $this->getUpdateBuilder()
                ->update('wolf_memberships_subscription')
                ->set('medical_certificate', $custom_fields['medical_certificate'] ?? null)
                ->set('medical_certificate_date', $custom_fields['medical_certificate_date'] ?? null)
                ->set('doctor', $custom_fields['doctor'] ?? null)
                ->set('medical_form', $custom_fields['medical_form'] ?? null)
                ->set('identity_photo', $custom_fields['identity_photo'] ?? null)
                ->set('agree_exit', isset($custom_fields['agree_exit']) && is_bool($custom_fields['agree_exit']) ? ($custom_fields['agree_exit'] ? 1 : 0) : null)
                ->set('agree_image', isset($custom_fields['agree_image']) && is_bool($custom_fields['agree_image']) ? ($custom_fields['agree_image'] ? 1 : 0) : 0)
                ->set('fields', json_encode($new_fields))
                ->where(['id' => $row['id']]);

            $builder->execute();
        }
    }
}
