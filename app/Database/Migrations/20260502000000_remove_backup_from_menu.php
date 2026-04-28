<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_remove_backup_from_menu extends Migration
{
    public function up(): void
    {
        $this->db->simpleQuery("DELETE FROM `ospos_modules` WHERE `module_id` = 'backup'");
    }

    public function down(): void
    {
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES ('module_backup', 'module_backup_desc', 120, 'backup')");
    }
}
