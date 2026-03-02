<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_sale_channel extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' ADD COLUMN sale_channel VARCHAR(20) NOT NULL DEFAULT \'store\'');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('sales') . ' DROP COLUMN sale_channel');
    }
}
