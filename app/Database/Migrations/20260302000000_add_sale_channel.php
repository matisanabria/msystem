<?php

namespace App\Database\Migrations;

use App\Libraries\MY_Migration;

class Migration_add_sale_channel extends MY_Migration
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
