<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_fix_service_tickets_grants extends Migration
{
    public function up(): void
    {
        $grants      = $this->db->prefixTable('grants');
        $permissions = $this->db->prefixTable('permissions');

        // Remove all location-specific service_tickets grants (migration gave everyone everything)
        $this->db->simpleQuery("
            DELETE g FROM `{$grants}` g
            JOIN `{$permissions}` p ON g.permission_id = p.permission_id
            WHERE p.module_id = 'service_tickets' AND p.location_id IS NOT NULL
        ");

        // Re-grant: for each (employee, location) with items access → grant service_tickets
        $this->db->simpleQuery("
            INSERT IGNORE INTO `{$grants}` (permission_id, person_id, menu_group)
            SELECT p_st.permission_id, g.person_id, g.menu_group
            FROM `{$grants}` g
            JOIN `{$permissions}` p_items
                ON g.permission_id = p_items.permission_id AND p_items.module_id = 'items'
            JOIN `{$permissions}` p_st
                ON p_st.module_id = 'service_tickets' AND p_st.location_id = p_items.location_id
        ");
    }

    public function down(): void
    {
        // Re-grant all employees to all service_tickets locations (reverts to migration 20260429 state)
        $grants      = $this->db->prefixTable('grants');
        $permissions = $this->db->prefixTable('permissions');
        $employees   = $this->db->prefixTable('employees');

        $this->db->simpleQuery("
            INSERT IGNORE INTO `{$grants}` (permission_id, person_id, menu_group)
            SELECT p.permission_id, e.person_id, 'home'
            FROM `{$permissions}` p
            CROSS JOIN `{$employees}` e
            WHERE p.module_id = 'service_tickets' AND p.location_id IS NOT NULL AND e.deleted = 0
        ");
    }
}
