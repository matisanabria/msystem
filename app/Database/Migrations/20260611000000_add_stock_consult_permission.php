<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_stock_consult_permission extends Migration
{
    public function up(): void
    {
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES ('sales_consult_stock', 'sales')");
        $this->db->simpleQuery("INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`, `menu_group`) VALUES ('sales_consult_stock', 1, 'home')");
    }

    public function down(): void
    {
        $this->db->simpleQuery("DELETE FROM `ospos_grants` WHERE permission_id = 'sales_consult_stock'");
        $this->db->simpleQuery("DELETE FROM `ospos_permissions` WHERE permission_id = 'sales_consult_stock'");
    }
}
