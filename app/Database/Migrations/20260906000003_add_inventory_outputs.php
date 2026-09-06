<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_inventory_outputs extends Migration
{
    public function up(): void
    {
        $table   = $this->db->prefixTable('inventory_outputs');
        $modules = $this->db->prefixTable('modules');
        $perms   = $this->db->prefixTable('permissions');
        $grants  = $this->db->prefixTable('grants');

        $this->db->query("CREATE TABLE IF NOT EXISTS `$table` (
            `output_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `item_id`            INT NOT NULL,
            `location_id`        INT NOT NULL,
            `quantity`           DECIMAL(15,3) NOT NULL,
            `reason`             ENUM('internal_use','damaged','sample','loss_theft','supplier_return','other') NOT NULL,
            `comment`            TEXT NULL DEFAULT NULL,
            `inventory_trans_id` INT NULL DEFAULT NULL,
            `person_id`          INT NOT NULL,
            `created_at`         DATETIME NOT NULL,
            `deleted`            TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`output_id`),
            KEY `idx_item` (`item_id`),
            KEY `idx_location` (`location_id`),
            KEY `idx_deleted` (`deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $mod_exists = $this->db->query(
            "SELECT module_id FROM `$modules` WHERE module_id = 'inventory_output'"
        )->getNumRows();

        if ($mod_exists === 0) {
            $this->db->query(
                "INSERT INTO `$modules` (module_id, name_lang_key, desc_lang_key, sort)
                 VALUES ('inventory_output', 'inventory_output', 'inventory_output_desc', 117)"
            );
        }

        $config_grants = $this->db->query(
            "SELECT person_id, menu_group FROM `$grants` WHERE permission_id = 'config'"
        )->getResultArray();

        // Bare permission row (location_id NULL) — required for the module to show up in the
        // menu at all: Module::get_allowed_home_modules()/get_allowed_office_modules() join
        // permissions.permission_id = modules.module_id, an exact match on 'inventory_output'.
        $perm_exists = $this->db->query(
            "SELECT permission_id FROM `$perms` WHERE permission_id = 'inventory_output'"
        )->getNumRows();

        if ($perm_exists === 0) {
            $this->db->query(
                "INSERT INTO `$perms` (permission_id, module_id, location_id)
                 VALUES ('inventory_output', 'inventory_output', NULL)"
            );
        }

        foreach ($config_grants as $grant) {
            $already = $this->db->query(
                "SELECT permission_id FROM `$grants` WHERE permission_id = 'inventory_output' AND person_id = ?",
                [(int)$grant['person_id']]
            )->getNumRows();

            if ($already === 0) {
                $this->db->query(
                    "INSERT INTO `$grants` (permission_id, person_id, menu_group) VALUES ('inventory_output', ?, ?)",
                    [(int)$grant['person_id'], $grant['menu_group']]
                );
            }
        }

        // One permission row PER active stock location — required by
        // Stock_location::get_undeleted_all()/get_allowed_locations(), which joins
        // permissions.location_id = stock_locations.location_id (the bare NULL-location
        // permission above never matches that join, so location-aware lookups need these too).
        $loc_table = $this->db->prefixTable('stock_locations');
        $locations = $this->db->query("SELECT location_id, location_name FROM `$loc_table` WHERE deleted = 0")->getResultArray();

        foreach ($locations as $location) {
            $loc_id   = (int)$location['location_id'];
            $perm_id  = 'inventory_output_' . str_replace(' ', '_', $location['location_name']);
            $perm_esc = $this->db->escape($perm_id);

            $perm_exists = $this->db->query(
                "SELECT permission_id FROM `$perms` WHERE permission_id = ?",
                [$perm_id]
            )->getNumRows();

            if ($perm_exists === 0) {
                $this->db->query(
                    "INSERT INTO `$perms` (permission_id, module_id, location_id) VALUES ($perm_esc, 'inventory_output', $loc_id)"
                );
            }

            // Only grant to employees who already hold 'config' (admins) — this is a
            // sensitive stock-adjustment action, not a routine per-employee one.
            foreach ($config_grants as $grant) {
                $already = $this->db->query(
                    "SELECT permission_id FROM `$grants` WHERE permission_id = ? AND person_id = ?",
                    [$perm_id, (int)$grant['person_id']]
                )->getNumRows();

                if ($already === 0) {
                    $this->db->query(
                        "INSERT INTO `$grants` (permission_id, person_id, menu_group) VALUES ($perm_esc, ?, ?)",
                        [(int)$grant['person_id'], $grant['menu_group']]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $table   = $this->db->prefixTable('inventory_outputs');
        $modules = $this->db->prefixTable('modules');
        $perms   = $this->db->prefixTable('permissions');
        $grants  = $this->db->prefixTable('grants');

        $this->db->query("DELETE FROM `$grants`  WHERE permission_id LIKE 'inventory\\_output%'");
        $this->db->query("DELETE FROM `$perms`   WHERE module_id     = 'inventory_output'");
        $this->db->query("DELETE FROM `$modules` WHERE module_id     = 'inventory_output'");
        $this->db->query("DROP TABLE IF EXISTS `$table`");
    }
}
