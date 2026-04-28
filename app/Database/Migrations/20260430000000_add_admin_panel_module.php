<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_admin_panel_module extends Migration
{
    public function up(): void
    {
        $modules_table     = $this->db->prefixTable('modules');
        $permissions_table = $this->db->prefixTable('permissions');
        $grants_table      = $this->db->prefixTable('grants');

        // Skip if already registered
        $exists = $this->db->query("SELECT module_id FROM `$modules_table` WHERE module_id = 'admin_panel'")->getNumRows();
        if ($exists > 0) {
            return;
        }

        // Register module
        $this->db->query("INSERT INTO `$modules_table` (module_id, name_lang_key, desc_lang_key, sort)
                          VALUES ('admin_panel', 'admin_panel', 'admin_panel_desc', 115)");

        // Register permission (global, no location)
        $perm_exists = $this->db->query("SELECT permission_id FROM `$permissions_table` WHERE permission_id = 'admin_panel'")->getNumRows();
        if ($perm_exists === 0) {
            $this->db->query("INSERT INTO `$permissions_table` (permission_id, module_id, location_id)
                              VALUES ('admin_panel', 'admin_panel', NULL)");
        }

        // Give admin_panel grant to all employees who currently have config grant
        $config_grants = $this->db->query("SELECT person_id, menu_group FROM `$grants_table` WHERE permission_id = 'config'")->getResultArray();

        foreach ($config_grants as $grant) {
            $already = $this->db->query(
                "SELECT permission_id FROM `$grants_table` WHERE permission_id = 'admin_panel' AND person_id = ?",
                [(int)$grant['person_id']]
            )->getNumRows();

            if ($already === 0) {
                $this->db->query(
                    "INSERT INTO `$grants_table` (permission_id, person_id, menu_group) VALUES ('admin_panel', ?, ?)",
                    [(int)$grant['person_id'], $grant['menu_group']]
                );
            }
        }
    }

    public function down(): void
    {
        $modules_table     = $this->db->prefixTable('modules');
        $permissions_table = $this->db->prefixTable('permissions');
        $grants_table      = $this->db->prefixTable('grants');

        $this->db->query("DELETE FROM `$grants_table`     WHERE permission_id = 'admin_panel'");
        $this->db->query("DELETE FROM `$permissions_table` WHERE permission_id = 'admin_panel'");
        $this->db->query("DELETE FROM `$modules_table`     WHERE module_id    = 'admin_panel'");
    }
}
