<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_person_identification extends Migration
{
    public function up(): void
    {
        log_message('info', 'Adding identification columns to people table.');
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('people')
            . ' ADD COLUMN `identification_type` VARCHAR(15) NULL DEFAULT NULL,'
            . ' ADD COLUMN `identification` VARCHAR(30) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        log_message('info', 'Dropping identification columns from people table.');
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('people')
            . ' DROP COLUMN `identification_type`,'
            . ' DROP COLUMN `identification`');
    }
}
