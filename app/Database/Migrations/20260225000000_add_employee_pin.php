<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_employee_pin extends Migration
{
    public function up(): void
    {
        log_message('info', 'Adding PIN column to employees table.');
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('employees') . ' ADD COLUMN `pin` VARCHAR(4) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        log_message('info', 'Dropping PIN column from employees table.');
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('employees') . ' DROP COLUMN `pin`');
    }
}
