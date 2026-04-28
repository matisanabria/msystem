<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_remove_office_concept extends Migration
{
    public function up(): void
    {
        $modules_table = $this->db->prefixTable('modules');
        $grants_table  = $this->db->prefixTable('grants');

        // Hide the office gateway module from home grid
        $this->db->query("UPDATE `$modules_table` SET sort = 0 WHERE module_id = 'office'");

        // Hide employees as standalone nav module (now lives inside admin_panel)
        $this->db->query("UPDATE `$modules_table` SET sort = 0 WHERE module_id = 'employees'");

        // Move all office-only grants to home so everything appears in main navbar
        $this->db->query("UPDATE `$grants_table` SET menu_group = 'home' WHERE menu_group = 'office'");
    }

    public function down(): void
    {
        $modules_table = $this->db->prefixTable('modules');
        $grants_table  = $this->db->prefixTable('grants');

        $this->db->query("UPDATE `$modules_table` SET sort = 80 WHERE module_id = 'employees'");
        $this->db->query("UPDATE `$modules_table` SET sort = 999 WHERE module_id = 'office'");
        // Note: cannot restore individual menu_group values — down() is best-effort
    }
}
