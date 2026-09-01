<?php

namespace App\Membership\Migration;

use App\Core\Migration\MigrationInterface;

class Migration_0_0_6 implements MigrationInterface
{

    public function up()
    {
        $this->addParticipantNbColumn();
    }

    public function down()
    {
    }

    private function addParticipantNbColumn()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'wolf_memberships_lesson';

        if ($wpdb->get_var("SHOW COLUMNS FROM `$tableName` LIKE 'participant_nb'") === null) {
            $wpdb->query("ALTER TABLE `$tableName` ADD `participant_nb` INT NOT NULL DEFAULT 0");
        }

        // Update participant_nb for existing lessons
        $wpdb->query("
            UPDATE `$tableName` wml
            SET wml.participant_nb = (
                SELECT COUNT(*)
                FROM {$wpdb->prefix}wolf_memberships_session wms
                WHERE wms.lesson_id = wml.id
            )
        ");

        // Add a trigger to update participant_nb when a session is added or removed
        $wpdb->query("
            CREATE TRIGGER update_participant_nb_after_insert
            AFTER INSERT ON {$wpdb->prefix}wolf_memberships_session
            FOR EACH ROW
            BEGIN
                UPDATE `$tableName`
                SET participant_nb = (SELECT COUNT(*)
                    FROM {$wpdb->prefix}wolf_memberships_session
                    WHERE lesson_id = NEW.lesson_id
                )
                WHERE id = NEW.lesson_id;
            END;
        ");

        $wpdb->query("
            CREATE TRIGGER update_participant_nb_after_delete
            AFTER DELETE ON {$wpdb->prefix}wolf_memberships_session
            FOR EACH ROW
            BEGIN
                UPDATE `$tableName`
                SET participant_nb = (SELECT COUNT(*)
                    FROM {$wpdb->prefix}wolf_memberships_session
                    WHERE lesson_id = OLD.lesson_id
                )
                WHERE id = OLD.lesson_id;
            END;
        ");

        // Add a trigger to update participant_nb when a session is updated
        $wpdb->query("
            CREATE TRIGGER update_participant_nb_after_update
            AFTER UPDATE ON {$wpdb->prefix}wolf_memberships_session
            FOR EACH ROW
            BEGIN
                IF OLD.lesson_id != NEW.lesson_id THEN
                    UPDATE `$tableName`
                    SET participant_nb = (SELECT COUNT(*)
                        FROM {$wpdb->prefix}wolf_memberships_session
                        WHERE lesson_id = OLD.lesson_id
                    )
                    WHERE id = OLD.lesson_id;

                    UPDATE `$tableName`
                    SET participant_nb = (SELECT COUNT(*)
                        FROM {$wpdb->prefix}wolf_memberships_session
                        WHERE lesson_id = NEW.lesson_id
                    )
                    WHERE id = NEW.lesson_id;
                END IF;
            END;
        ");
    }
}