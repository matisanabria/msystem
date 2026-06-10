<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_create_activity_logs extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('activity_logs');

        $exists = $this->db->query("SHOW TABLES LIKE '{$table}'")->getNumRows() > 0;
        if (!$exists) {
            $this->db->simpleQuery("
                CREATE TABLE `{$table}` (
                    `log_id`       INT(11) NOT NULL AUTO_INCREMENT,
                    `log_type`     VARCHAR(30) NOT NULL,
                    `log_date`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `employee_id`  INT(11) DEFAULT NULL,
                    `location_id`  INT(11) DEFAULT NULL,
                    `description`  TEXT NOT NULL,
                    `reference_id` INT(11) DEFAULT NULL,
                    `ip_address`   VARCHAR(45) DEFAULT NULL,
                    PRIMARY KEY (`log_id`),
                    KEY `idx_type` (`log_type`),
                    KEY `idx_date` (`log_date`),
                    KEY `idx_employee` (`employee_id`),
                    KEY `idx_location` (`location_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
        }

        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES ('module_logs', 'module_logs_desc', 95, 'logs')");
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES ('logs', 'logs')");
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES ('logs', 1, 'home')");
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('activity_logs');
        $this->db->simpleQuery("DROP TABLE IF EXISTS `{$table}`");
        $this->db->simpleQuery("DELETE FROM `ospos_grants` WHERE permission_id = 'logs'");
        $this->db->simpleQuery("DELETE FROM `ospos_permissions` WHERE permission_id = 'logs'");
        $this->db->simpleQuery("DELETE FROM `ospos_modules` WHERE module_id = 'logs'");
    }
}
