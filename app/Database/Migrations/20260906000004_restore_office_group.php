<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Reverses the effect of 20260430000001_remove_office_concept for a specific subset of
 * modules. That migration flattened everything into 'home' when there weren't enough
 * back-office-only modules to justify the split. Since then admin_panel, discount_approvals,
 * logs and inventory_output were added — all back-office/occasional-use, never touched by
 * cashiers — so the split earns its keep again for exactly these four.
 *
 * Everything else stays in 'home' as-is. This does not re-run the old migration's up(); it
 * moves a curated set of grants, and re-adds the 'office' gateway icon + a 'home' return icon
 * for the people who now need to reach it.
 */
class Migration_restore_office_group extends Migration
{
    private const OFFICE_MODULES = ['admin_panel', 'discount_approvals', 'logs', 'inventory_output'];

    public function up(): void
    {
        $modules_table = $this->db->prefixTable('modules');
        $grants_table  = $this->db->prefixTable('grants');

        // Make sure the office gateway module is enabled (Config's "show office group" toggle
        // sets this; ensure it regardless of whatever state that runtime toggle is in).
        $this->db->query("UPDATE `$modules_table` SET sort = 999 WHERE module_id = 'office'");

        // Move the four back-office modules' bare grants into the office context.
        $in_list = implode(',', array_map(fn ($m) => $this->db->escape($m), self::OFFICE_MODULES));
        $this->db->query("UPDATE `$grants_table` SET menu_group = 'office' WHERE permission_id IN ($in_list)");

        // Everyone who now has at least one of those grants needs a way to reach /office
        // (an 'office' grant visible while in 'home') and a way back (a 'home' grant visible
        // while in 'office'). Add whichever of the two each person is missing.
        $people = $this->db->query(
            "SELECT DISTINCT person_id FROM `$grants_table` WHERE permission_id IN ($in_list)"
        )->getResultArray();

        // (permission_id, person_id) is the PRIMARY KEY on ospos_grants — there is at most one
        // grant row per person per permission, and menu_group is just an attribute of that one
        // row (not a list), so reaching "visible in both contexts" means upserting to 'both',
        // never inserting a second row for a permission_id the person already holds.
        foreach ($people as $row) {
            $person_id = (int)$row['person_id'];
            $this->_ensure_visible_in('office', $person_id, 'home');
            $this->_ensure_visible_in('home', $person_id, 'office');
        }
    }

    /**
     * Makes sure $person_id's grant on $permission_id is visible while browsing $extra_context,
     * without dropping whatever context it was already visible in.
     */
    private function _ensure_visible_in(string $permission_id, int $person_id, string $extra_context): void
    {
        $grants_table = $this->db->prefixTable('grants');

        $existing = $this->db->query(
            "SELECT menu_group FROM `$grants_table` WHERE permission_id = ? AND person_id = ?",
            [$permission_id, $person_id]
        )->getRowArray();

        if ($existing === null) {
            $this->db->query(
                "INSERT INTO `$grants_table` (permission_id, person_id, menu_group) VALUES (?, ?, ?)",
                [$permission_id, $person_id, $extra_context]
            );
            return;
        }

        if ($existing['menu_group'] !== $extra_context && $existing['menu_group'] !== 'both') {
            $this->db->query(
                "UPDATE `$grants_table` SET menu_group = 'both' WHERE permission_id = ? AND person_id = ?",
                [$permission_id, $person_id]
            );
        }
    }

    public function down(): void
    {
        $grants_table = $this->db->prefixTable('grants');

        $in_list = implode(',', array_map(fn ($m) => $this->db->escape($m), self::OFFICE_MODULES));
        $this->db->query("UPDATE `$grants_table` SET menu_group = 'home' WHERE permission_id IN ($in_list) AND menu_group = 'office'");

        // Deliberately does not touch the 'office'/'home' toggle grants or modules.sort for
        // 'office': some of those rows (e.g. person 1's original 'office' grant) predate this
        // migration entirely, and we have no reliable way to tell those apart from the ones we
        // added — best-effort down(), same as 20260430000001_remove_office_concept's.
    }
}
