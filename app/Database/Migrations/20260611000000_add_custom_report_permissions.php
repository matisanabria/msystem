<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_custom_report_permissions extends Migration
{
    private array $permissions = [
        'reports_monthly_financial',
        'reports_service_tickets',
        'reports_graphical',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission_id) {
            $exists = $this->db->table($this->db->prefixTable('permissions'))
                ->where('permission_id', $permission_id)
                ->countAllResults();

            if (!$exists) {
                $this->db->table($this->db->prefixTable('permissions'))->insert([
                    'permission_id' => $permission_id,
                    'module_id'     => 'reports',
                ]);
            }

            $grant_exists = $this->db->table($this->db->prefixTable('grants'))
                ->where('permission_id', $permission_id)
                ->where('person_id', 1)
                ->countAllResults();

            if (!$grant_exists) {
                $this->db->table($this->db->prefixTable('grants'))->insert([
                    'permission_id' => $permission_id,
                    'person_id'     => 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->permissions as $permission_id) {
            $this->db->table($this->db->prefixTable('grants'))
                ->where('permission_id', $permission_id)
                ->delete();

            $this->db->table($this->db->prefixTable('permissions'))
                ->where('permission_id', $permission_id)
                ->delete();
        }
    }
}
