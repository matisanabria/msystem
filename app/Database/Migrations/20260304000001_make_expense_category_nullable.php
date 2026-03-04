<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_make_expense_category_nullable extends Migration
{
    public function up(): void
    {
        $this->db->simpleQuery("ALTER TABLE `" . $this->db->prefixTable('expenses') . "`
            DROP FOREIGN KEY `ospos_expenses_ibfk_1`");

        $this->db->simpleQuery("ALTER TABLE `" . $this->db->prefixTable('expenses') . "`
            MODIFY `expense_category_id` int(10) DEFAULT NULL");

        log_message('info', 'expense_category_id made nullable');
    }

    public function down(): void
    {
        $this->db->simpleQuery("ALTER TABLE `" . $this->db->prefixTable('expenses') . "`
            MODIFY `expense_category_id` int(10) NOT NULL");

        $this->db->simpleQuery("ALTER TABLE `" . $this->db->prefixTable('expenses') . "`
            ADD CONSTRAINT `ospos_expenses_ibfk_1` FOREIGN KEY (`expense_category_id`)
            REFERENCES `" . $this->db->prefixTable('expense_categories') . "` (`expense_category_id`)");
    }
}
