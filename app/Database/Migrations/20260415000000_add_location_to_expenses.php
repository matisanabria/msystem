<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_location_to_expenses extends Migration
{
    public function up(): void
    {
        log_message('info', 'Adding location_id to expenses');

        $table      = $this->db->prefixTable('expenses');
        $loc_table  = $this->db->prefixTable('stock_locations');
        $emp_table  = $this->db->prefixTable('employees');

        // 1. Add location_id column
        $this->db->simpleQuery("ALTER TABLE `{$table}` ADD COLUMN `location_id` INT(10) NULL DEFAULT NULL");

        // 2. Backfill: assign existing expenses to the first active location
        $first = $this->db->query("SELECT location_id FROM `{$loc_table}` WHERE deleted = 0 ORDER BY location_id ASC LIMIT 1")->getRow();
        if ($first) {
            $loc_id = (int)$first->location_id;
            $this->db->simpleQuery("UPDATE `{$table}` SET location_id = {$loc_id} WHERE location_id IS NULL");
        }

        // 3. Create expense permissions for each existing location
        $locations = $this->db->query("SELECT location_id, location_name FROM `{$loc_table}` WHERE deleted = 0")->getResultArray();
        $employees = $this->db->query("SELECT person_id FROM `{$emp_table}` WHERE deleted = 0")->getResultArray();

        foreach ($locations as $location) {
            $loc_id   = (int)$location['location_id'];
            $loc_name = $location['location_name'];
            $perm_id  = 'expenses_' . str_replace(' ', '_', $loc_name);
            $perm_esc = $this->db->escape($perm_id);

            $this->db->simpleQuery("INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`, `location_id`) VALUES ({$perm_esc}, 'expenses', {$loc_id})");

            foreach ($employees as $emp) {
                $pid = (int)$emp['person_id'];
                $this->db->simpleQuery("INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES ({$perm_esc}, {$pid}, 'home')");
            }
        }

        log_message('info', 'location_id added to expenses successfully');
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('expenses');

        $this->db->simpleQuery("ALTER TABLE `{$table}` DROP COLUMN IF EXISTS `location_id`");
        $this->db->simpleQuery("DELETE FROM `ospos_grants` WHERE permission_id LIKE 'expenses\\_%'");
        $this->db->simpleQuery("DELETE FROM `ospos_permissions` WHERE module_id = 'expenses' AND location_id IS NOT NULL");
    }
}
