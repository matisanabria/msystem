<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_expense_categories extends Migration
{
    public function up(): void
    {
        $categories = [
            'Sueldo',
            'Insumos',
            'Alquiler',
            'Servicios',
            'Mantenimiento',
            'Transporte',
            'Marketing',
            'Otros',
        ];

        foreach ($categories as $name) {
            $this->db->simpleQuery(
                "INSERT IGNORE INTO `" . $this->db->prefixTable('expense_categories') . "`
                (`category_name`, `category_description`, `deleted`) VALUES (" . $this->db->escape($name) . ", '', 0)"
            );
        }

        log_message('info', 'Default expense categories added');
    }

    public function down(): void
    {
        $categories = ['Sueldo', 'Insumos', 'Alquiler', 'Servicios', 'Mantenimiento', 'Transporte', 'Marketing', 'Otros'];

        $names = implode(',', array_map(fn($n) => $this->db->escape($n), $categories));
        $this->db->simpleQuery(
            "DELETE FROM `" . $this->db->prefixTable('expense_categories') . "` WHERE `category_name` IN ($names)"
        );
    }
}
