<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_discount_approvals extends Migration
{
    public function up(): void
    {
        $table      = $this->db->prefixTable('discount_approvals');
        $modules    = $this->db->prefixTable('modules');
        $perms      = $this->db->prefixTable('permissions');
        $grants     = $this->db->prefixTable('grants');

        $this->db->query("CREATE TABLE IF NOT EXISTS `$table` (
            `approval_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `location_id`   INT NOT NULL,
            `requested_by`  INT NOT NULL,
            `discount`      DECIMAL(15,2) NOT NULL,
            `discount_type` TINYINT(1) NOT NULL DEFAULT 0,
            `item_name`     VARCHAR(255) NULL DEFAULT NULL,
            `item_price`    DECIMAL(15,2) NULL DEFAULT NULL,
            `item_quantity` DECIMAL(15,2) NULL DEFAULT NULL,
            `auth_code`     CHAR(4) NULL DEFAULT NULL,
            `status`        ENUM('pending','approved','used','expired') NOT NULL DEFAULT 'pending',
            `created_at`    DATETIME NOT NULL,
            `expires_at`    DATETIME NULL DEFAULT NULL,
            `approved_by`   INT NULL DEFAULT NULL,
            PRIMARY KEY (`approval_id`),
            KEY `idx_status` (`status`),
            KEY `idx_location_status` (`location_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $mod_exists = $this->db->query(
            "SELECT module_id FROM `$modules` WHERE module_id = 'discount_approvals'"
        )->getNumRows();

        if ($mod_exists === 0) {
            $this->db->query(
                "INSERT INTO `$modules` (module_id, name_lang_key, desc_lang_key, sort)
                 VALUES ('discount_approvals', 'discount_approvals', 'discount_approvals_desc', 116)"
            );
        }

        $perm_exists = $this->db->query(
            "SELECT permission_id FROM `$perms` WHERE permission_id = 'discount_approvals'"
        )->getNumRows();

        if ($perm_exists === 0) {
            $this->db->query(
                "INSERT INTO `$perms` (permission_id, module_id, location_id)
                 VALUES ('discount_approvals', 'discount_approvals', NULL)"
            );
        }

        $config_grants = $this->db->query(
            "SELECT person_id, menu_group FROM `$grants` WHERE permission_id = 'config'"
        )->getResultArray();

        foreach ($config_grants as $grant) {
            $already = $this->db->query(
                "SELECT permission_id FROM `$grants` WHERE permission_id = 'discount_approvals' AND person_id = ?",
                [(int)$grant['person_id']]
            )->getNumRows();

            if ($already === 0) {
                $this->db->query(
                    "INSERT INTO `$grants` (permission_id, person_id, menu_group) VALUES ('discount_approvals', ?, ?)",
                    [(int)$grant['person_id'], $grant['menu_group']]
                );
            }
        }
    }

    public function down(): void
    {
        $table   = $this->db->prefixTable('discount_approvals');
        $modules = $this->db->prefixTable('modules');
        $perms   = $this->db->prefixTable('permissions');
        $grants  = $this->db->prefixTable('grants');

        $this->db->query("DELETE FROM `$grants`  WHERE permission_id = 'discount_approvals'");
        $this->db->query("DELETE FROM `$perms`   WHERE permission_id = 'discount_approvals'");
        $this->db->query("DELETE FROM `$modules` WHERE module_id     = 'discount_approvals'");
        $this->db->query("DROP TABLE IF EXISTS `$table`");
    }
}
