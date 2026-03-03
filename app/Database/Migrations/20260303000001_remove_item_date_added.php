<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_remove_item_date_added extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('items') . ' DROP COLUMN IF EXISTS `date_added`');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('items') . ' ADD COLUMN `date_added` TIMESTAMP NULL DEFAULT NULL');
    }
}
