<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Changelog" section under Oficina: a short list of changes for the client.
 *
 * The module has `sort = 0` on purpose: Module::get_allowed_*_modules() skip sort 0, so it never shows
 * as a card or navbar icon. It is reached through a small link in /office, gated by this permission.
 * The permission is global (no location), like `logs` and `discount_approvals`.
 */
class Migration_add_changelog_module extends Migration
{
    public function up(): void
    {
        $modules     = $this->db->prefixTable('modules');
        $permissions = $this->db->prefixTable('permissions');
        $grants      = $this->db->prefixTable('grants');

        $this->db->query(
            "INSERT IGNORE INTO `$modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES (?, ?, ?, ?)",
            ['module_changelog', 'module_changelog_desc', 0, 'changelog']
        );
        $this->db->query(
            "INSERT IGNORE INTO `$permissions` (`permission_id`, `module_id`) VALUES (?, ?)",
            ['changelog', 'changelog']
        );
        // PK is (permission_id, person_id): one row per person, so INSERT IGNORE is the upsert we need here.
        $this->db->query(
            "INSERT IGNORE INTO `$grants` (`permission_id`, `person_id`, `menu_group`) VALUES (?, ?, ?)",
            ['changelog', 1, 'office']
        );
    }

    public function down(): void
    {
        $this->db->query("DELETE FROM `" . $this->db->prefixTable('grants') . "` WHERE permission_id = 'changelog'");
        $this->db->query("DELETE FROM `" . $this->db->prefixTable('permissions') . "` WHERE permission_id = 'changelog'");
        $this->db->query("DELETE FROM `" . $this->db->prefixTable('modules') . "` WHERE module_id = 'changelog'");
    }
}
