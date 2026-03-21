<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_assistances extends Migration
{
    public function up(): void
    {
        log_message('info', 'Adding assistances module');

        $table = $this->db->prefixTable('assistances');

        $this->db->simpleQuery("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `assistance_id` INT(10) NOT NULL AUTO_INCREMENT,
                `item_id` INT(10) DEFAULT NULL,
                `customer_id` INT(10) DEFAULT NULL,
                `supplier_id` INT(10) DEFAULT NULL,
                `employee_id` INT(10) NOT NULL,
                `item_name` VARCHAR(255) NOT NULL,
                `problem_description` TEXT NOT NULL,
                `supplier_notes` TEXT DEFAULT NULL,
                `resolution` TEXT DEFAULT NULL,
                `status` VARCHAR(25) NOT NULL DEFAULT 'received',
                `affects_stock` TINYINT(1) NOT NULL DEFAULT 1,
                `stock_adjusted` TINYINT(1) NOT NULL DEFAULT 0,
                `location_id` INT(10) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `sent_date` DATE DEFAULT NULL,
                `return_date` DATE DEFAULT NULL,
                `delivered_date` DATE DEFAULT NULL,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted` INT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`assistance_id`),
                KEY `item_id` (`item_id`),
                KEY `customer_id` (`customer_id`),
                KEY `supplier_id` (`supplier_id`),
                KEY `employee_id` (`employee_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");

        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES ('module_assistances', 'module_assistances_desc', 16, 'assistances')");
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES ('assistances', 'assistances')");
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES ('assistances', 1, 'home')");

        log_message('info', 'Assistances module added successfully');
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('assistances');

        $this->db->simpleQuery("DROP TABLE IF EXISTS `{$table}`");
        $this->db->simpleQuery("DELETE FROM `ospos_grants` WHERE `permission_id` = 'assistances'");
        $this->db->simpleQuery("DELETE FROM `ospos_permissions` WHERE `permission_id` = 'assistances'");
        $this->db->simpleQuery("DELETE FROM `ospos_modules` WHERE `module_id` = 'assistances'");
    }
}
