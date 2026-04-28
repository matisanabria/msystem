<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_location_to_service_tickets extends Migration
{
    public function up(): void
    {
        $table     = $this->db->prefixTable('service_tickets');
        $loc_table = $this->db->prefixTable('stock_locations');
        $emp_table = $this->db->prefixTable('employees');

        // Skip if already applied
        $has_column = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE 'location_id'")->getNumRows() > 0;
        if ($has_column) {
            return;
        }

        $this->db->simpleQuery("ALTER TABLE `{$table}` ADD COLUMN `location_id` INT(10) NULL DEFAULT NULL");

        // Backfill existing tickets to the first active location
        $first = $this->db->query("SELECT location_id FROM `{$loc_table}` WHERE deleted = 0 ORDER BY location_id ASC LIMIT 1")->getRow();
        if ($first) {
            $loc_id = (int)$first->location_id;
            $this->db->simpleQuery("UPDATE `{$table}` SET location_id = {$loc_id} WHERE location_id IS NULL");
        }

        // Create service_tickets permissions for each active location
        $locations = $this->db->query("SELECT location_id, location_name FROM `{$loc_table}` WHERE deleted = 0")->getResultArray();
        $employees = $this->db->query("SELECT person_id FROM `{$emp_table}` WHERE deleted = 0")->getResultArray();

        foreach ($locations as $location) {
            $loc_id   = (int)$location['location_id'];
            $loc_name = $location['location_name'];
            $perm_id  = 'service_tickets_' . str_replace(' ', '_', $loc_name);
            $perm_esc = $this->db->escape($perm_id);

            $this->db->simpleQuery("INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`, `location_id`) VALUES ({$perm_esc}, 'service_tickets', {$loc_id})");

            foreach ($employees as $emp) {
                $pid = (int)$emp['person_id'];
                $this->db->simpleQuery("INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES ({$perm_esc}, {$pid}, 'home')");
            }
        }
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('service_tickets');

        $this->db->simpleQuery("ALTER TABLE `{$table}` DROP COLUMN IF EXISTS `location_id`");
        $this->db->simpleQuery("DELETE FROM `ospos_grants` WHERE permission_id LIKE 'service\\_tickets\\_%'");
        $this->db->simpleQuery("DELETE FROM `ospos_permissions` WHERE module_id = 'service_tickets' AND location_id IS NOT NULL");
    }
}
