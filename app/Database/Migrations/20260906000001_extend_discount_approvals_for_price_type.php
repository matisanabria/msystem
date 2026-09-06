<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_extend_discount_approvals_for_price_type extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('discount_approvals');

        $this->db->query("ALTER TABLE `$table`
            ADD COLUMN `request_type` ENUM('discount','price_type') NOT NULL DEFAULT 'discount' AFTER `approval_id`,
            ADD COLUMN `price_type` TINYINT(1) NULL DEFAULT NULL AFTER `discount_type`");
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('discount_approvals');

        $this->db->query("ALTER TABLE `$table`
            DROP COLUMN `request_type`,
            DROP COLUMN `price_type`");
    }
}
