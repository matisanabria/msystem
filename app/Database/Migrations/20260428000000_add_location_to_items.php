<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_location_to_items extends Migration
{
    public function up(): void
    {
        log_message('info', 'Adding location_id to items');

        $table     = $this->db->prefixTable('items');
        $loc_table = $this->db->prefixTable('stock_locations');

        $this->db->simpleQuery("ALTER TABLE `{$table}` ADD COLUMN `location_id` INT(10) NULL DEFAULT NULL");

        $first = $this->db->query("SELECT location_id FROM `{$loc_table}` WHERE deleted = 0 ORDER BY location_id ASC LIMIT 1")->getRow();
        if ($first) {
            $loc_id = (int)$first->location_id;
            $this->db->simpleQuery("UPDATE `{$table}` SET location_id = {$loc_id} WHERE location_id IS NULL");
        }

        log_message('info', 'location_id added to items successfully');
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('items');
        $this->db->simpleQuery("ALTER TABLE `{$table}` DROP COLUMN IF EXISTS `location_id`");
    }
}
