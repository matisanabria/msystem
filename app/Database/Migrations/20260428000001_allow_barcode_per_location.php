<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_allow_barcode_per_location extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('items');

        // Drop old global unique on item_number only if it still exists
        $has_old_index = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = 'item_number'"
        )->getNumRows() > 0;

        if ($has_old_index) {
            $this->db->simpleQuery("ALTER TABLE `{$table}` DROP INDEX `item_number`");
        }

        // Only proceed if the composite unique doesn't exist yet
        $has_new_index = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = 'item_number_per_location'"
        )->getNumRows() > 0;

        if ($has_new_index) {
            return;
        }

        // Among active items: keep highest item_id per (item_number, location_id), soft-delete the rest
        $this->db->query("
            UPDATE `{$table}` t1
            INNER JOIN `{$table}` t2
                ON  t1.item_number = t2.item_number
                AND t1.location_id = t2.location_id
                AND t1.item_id     < t2.item_id
                AND t2.deleted     = 0
            SET t1.deleted = 1
            WHERE t1.deleted = 0
              AND t1.item_number IS NOT NULL
              AND t1.item_number != ''
        ");

        // Deleted items that share (item_number, location_id) with any other item block the UNIQUE key.
        // NULL out their barcode — they're deleted so the value is irrelevant.
        $this->db->query("
            UPDATE `{$table}` t1
            INNER JOIN `{$table}` t2
                ON  t1.item_number = t2.item_number
                AND t1.location_id = t2.location_id
                AND t1.item_id    != t2.item_id
            SET t1.item_number = NULL
            WHERE t1.deleted = 1
              AND t1.item_number IS NOT NULL
              AND t1.item_number != ''
        ");

        $this->db->simpleQuery(
            "ALTER TABLE `{$table}` ADD UNIQUE KEY `item_number_per_location` (`item_number`, `location_id`)"
        );
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('items');

        $has_composite = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = 'item_number_per_location'"
        )->getNumRows() > 0;

        if ($has_composite) {
            $this->db->simpleQuery("ALTER TABLE `{$table}` DROP INDEX `item_number_per_location`");
        }

        $has_old = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = 'item_number'"
        )->getNumRows() > 0;

        if (!$has_old) {
            $this->db->simpleQuery("ALTER TABLE `{$table}` ADD UNIQUE KEY `item_number` (`item_number`)");
        }
    }
}
