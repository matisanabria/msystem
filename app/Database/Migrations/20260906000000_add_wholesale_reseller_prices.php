<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_wholesale_reseller_prices extends Migration
{
    public function up(): void
    {
        $items = $this->db->prefixTable('items');

        $this->db->query("ALTER TABLE `$items`
            ADD COLUMN `price_wholesale` DECIMAL(15,2) NULL DEFAULT NULL AFTER `unit_price`,
            ADD COLUMN `price_reseller`  DECIMAL(15,2) NULL DEFAULT NULL AFTER `price_wholesale`");

        // Backfill: both start equal to the current sale price
        $this->db->query("UPDATE `$items`
            SET price_wholesale = unit_price, price_reseller = unit_price
            WHERE price_wholesale IS NULL OR price_reseller IS NULL");
    }

    public function down(): void
    {
        $items = $this->db->prefixTable('items');

        $this->db->query("ALTER TABLE `$items`
            DROP COLUMN `price_wholesale`,
            DROP COLUMN `price_reseller`");
    }
}
