<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_backup_module extends Migration
{
    public function up(): void
    {
        log_message('info', 'Adding backup module');

        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES ('module_backup', 'module_backup_desc', 120, 'backup')");
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES ('backup', 'backup')");
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES ('backup', 1, 'office')");

        log_message('info', 'Backup module added successfully');
    }

    public function down(): void
    {
        $this->db->simpleQuery("DELETE FROM `ospos_grants` WHERE `permission_id` = 'backup'");
        $this->db->simpleQuery("DELETE FROM `ospos_permissions` WHERE `permission_id` = 'backup'");
        $this->db->simpleQuery("DELETE FROM `ospos_modules` WHERE `module_id` = 'backup'");
    }
}
