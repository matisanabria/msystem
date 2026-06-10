<?php

namespace App\Models;

use CodeIgniter\Model;

class Activity_log extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'log_id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'log_type',
        'log_date',
        'employee_id',
        'location_id',
        'description',
        'reference_id',
        'ip_address',
    ];

    public function log(string $type, string $description, ?int $employee_id, ?int $location_id, ?int $reference_id, ?string $ip): void
    {
        $data = [
            'log_type'     => $type,
            'description'  => $description,
            'employee_id'  => $employee_id,
            'location_id'  => $location_id,
            'reference_id' => $reference_id,
            'ip_address'   => $ip,
        ];
        $this->insert($data);
    }

    public function search(array $filters, int $limit, int $offset): array
    {
        $prefix = $this->db->getPrefix();
        $parts = $this->_build_union_parts($filters, $prefix);

        if (empty($parts)) {
            return [];
        }

        $sql = implode(' UNION ALL ', $parts);
        $sql = "SELECT * FROM ({$sql}) AS logs_union ORDER BY log_date DESC";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return $this->db->query($sql)->getResultArray();
    }

    public function get_total(array $filters): int
    {
        $prefix = $this->db->getPrefix();
        $parts = $this->_build_union_parts($filters, $prefix);

        if (empty($parts)) {
            return 0;
        }

        $sql = implode(' UNION ALL ', $parts);
        $sql = "SELECT COUNT(*) AS total FROM ({$sql}) AS logs_union";

        $row = $this->db->query($sql)->getRow();
        return $row ? (int)$row->total : 0;
    }

    public function get_employees(): array
    {
        $builder = $this->db->table('people AS people');
        $builder->select("people.person_id, CONCAT(people.first_name, ' ', people.last_name) AS full_name");
        $builder->join('employees AS employees', 'employees.person_id = people.person_id');
        $builder->where('employees.deleted', 0);
        $builder->orderBy('people.first_name');
        return $builder->get()->getResultArray();
    }

    private function _where_clause(array $conditions): string
    {
        $conditions = array_filter($conditions);
        return empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }

    private function _build_union_parts(array $filters, string $prefix): array
    {
        $parts = [];
        $type_filter = $filters['log_type'] ?? '';
        $emp_filter  = isset($filters['employee_id']) ? (int)$filters['employee_id'] : 0;
        $loc_filter  = isset($filters['location_id']) ? (int)$filters['location_id'] : 0;
        $date_from   = $filters['date_from'] ?? '';
        $date_to     = $filters['date_to'] ?? '';

        // Inventory part
        if (empty($type_filter) || $type_filter === 'inventory') {
            $conds = $this->_date_conditions('inv.trans_date', $date_from, $date_to);
            if ($emp_filter > 0) $conds[] = "inv.trans_user = {$emp_filter}";
            if ($loc_filter > 0) $conds[] = "inv.trans_location = {$loc_filter}";
            $where_clause = $this->_where_clause($conds);

            $parts[] = "
                SELECT
                    'inventory' AS log_type,
                    inv.trans_date AS log_date,
                    CONCAT(p.first_name, ' ', p.last_name) AS employee_name,
                    COALESCE(loc.location_name, '') AS location_name,
                    CONCAT(COALESCE(i.name,'?'), ' | ', inv.trans_comment, ' | Cant: ', inv.trans_inventory) AS description,
                    inv.trans_items AS reference_id,
                    inv.trans_user AS employee_id,
                    inv.trans_location AS location_id,
                    NULL AS ip_address
                FROM `{$prefix}inventory` inv
                LEFT JOIN `{$prefix}people` p ON p.person_id = inv.trans_user
                LEFT JOIN `{$prefix}stock_locations` loc ON loc.location_id = inv.trans_location
                LEFT JOIN `{$prefix}items` i ON i.item_id = inv.trans_items
                {$where_clause}
            ";
        }

        // Sales part
        if (empty($type_filter) || $type_filter === 'sale') {
            $conds = $this->_date_conditions('s.sale_time', $date_from, $date_to);
            if ($emp_filter > 0) $conds[] = "s.employee_id = {$emp_filter}";
            // Sales have no location_id — skip loc_filter for sales
            $where_clause = $this->_where_clause($conds);

            $parts[] = "
                SELECT
                    'sale' AS log_type,
                    s.sale_time AS log_date,
                    CONCAT(p.first_name, ' ', p.last_name) AS employee_name,
                    '' AS location_name,
                    CONCAT('Venta #', s.sale_id, IF(s.invoice_number IS NOT NULL AND s.invoice_number != '', CONCAT(' | Factura: ', s.invoice_number), '')) AS description,
                    s.sale_id AS reference_id,
                    s.employee_id,
                    NULL AS location_id,
                    NULL AS ip_address
                FROM `{$prefix}sales` s
                LEFT JOIN `{$prefix}people` p ON p.person_id = s.employee_id
                {$where_clause}
            ";
        }

        // Activity logs part (login, logout, ticket_status, etc.)
        $activity_types = ['login', 'logout', 'ticket_status'];
        $is_activity_type = in_array($type_filter, $activity_types);

        if (empty($type_filter) || $is_activity_type) {
            $conds = $this->_date_conditions('al.log_date', $date_from, $date_to);
            if (!empty($type_filter) && $is_activity_type) {
                $esc = $this->db->escape($type_filter);
                $conds[] = "al.log_type = {$esc}";
            }
            if ($emp_filter > 0) $conds[] = "al.employee_id = {$emp_filter}";
            if ($loc_filter > 0) $conds[] = "(al.location_id = {$loc_filter} OR al.location_id IS NULL)";
            $where_clause = $this->_where_clause($conds);

            $parts[] = "
                SELECT
                    al.log_type,
                    al.log_date,
                    COALESCE(CONCAT(p.first_name, ' ', p.last_name), '') AS employee_name,
                    COALESCE(loc.location_name, '') AS location_name,
                    al.description,
                    al.reference_id,
                    al.employee_id,
                    al.location_id,
                    al.ip_address
                FROM `{$prefix}activity_logs` al
                LEFT JOIN `{$prefix}people` p ON p.person_id = al.employee_id
                LEFT JOIN `{$prefix}stock_locations` loc ON loc.location_id = al.location_id
                {$where_clause}
            ";
        }

        return $parts;
    }

    private function _date_conditions(string $column, string $from, string $to): array
    {
        $conds = [];
        if (!empty($from)) {
            $esc = $this->db->escape($from . ' 00:00:00');
            $conds[] = "{$column} >= {$esc}";
        }
        if (!empty($to)) {
            $esc = $this->db->escape($to . ' 23:59:59');
            $conds[] = "{$column} <= {$esc}";
        }
        return $conds;
    }
}
