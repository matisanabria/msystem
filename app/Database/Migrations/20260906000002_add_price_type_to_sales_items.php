<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_price_type_to_sales_items extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('sales_items');

        // price_type: 0 = venta (default), 1 = mayorista, 2 = revendedor
        $this->db->query("ALTER TABLE `$table`
            ADD COLUMN `price_type` TINYINT(1) NOT NULL DEFAULT 0 AFTER `item_unit_price`,
            ADD COLUMN `price_approval_id` INT UNSIGNED NULL DEFAULT NULL AFTER `price_type`");
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('sales_items');

        $this->db->query("ALTER TABLE `$table`
            DROP COLUMN `price_type`,
            DROP COLUMN `price_approval_id`");
    }
}
