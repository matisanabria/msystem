<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;
use stdClass;

class Assistance extends Model
{
    protected $table = 'assistances';
    protected $primaryKey = 'assistance_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'item_id',
        'customer_id',
        'supplier_id',
        'employee_id',
        'item_name',

        'problem_description',
        'supplier_notes',
        'resolution',
        'status',
        'affects_stock',
        'stock_adjusted',
        'location_id',
        'sent_date',
        'return_date',
        'delivered_date',
        'deleted'
    ];

    public function exists(int $assistance_id): bool
    {
        $builder = $this->db->table('assistances');
        $builder->where('assistance_id', $assistance_id);
        $builder->where('deleted', 0);

        return ($builder->get()->getNumRows() == 1);
    }

    public function get_total_rows(): int
    {
        $builder = $this->db->table('assistances');
        $builder->where('deleted', 0);

        return $builder->countAllResults();
    }

    public function get_info(int $assistance_id): object
    {
        $builder = $this->db->table('assistances AS assistances');
        $builder->select('assistances.*, CONCAT(customer.first_name, " ", customer.last_name) AS customer_name, customer.phone_number AS customer_phone, customer.identification_type AS customer_identification_type, customer.identification AS customer_identification, CONCAT(employee.first_name, " ", employee.last_name) AS employee_name, suppliers.company_name AS supplier_name');
        $builder->join('people AS customer', 'customer.person_id = assistances.customer_id', 'left');
        $builder->join('people AS employee', 'employee.person_id = assistances.employee_id', 'left');
        $builder->join('suppliers', 'suppliers.person_id = assistances.supplier_id', 'left');
        $builder->where('assistance_id', $assistance_id);

        $query = $builder->get();

        if ($query->getNumRows() == 1) {
            return $query->getRow();
        }

        return $this->getEmptyObject('assistances');
    }

    private function getEmptyObject(string $table_name): object
    {
        $empty_obj = new stdClass();

        foreach ($this->db->getFieldData($table_name) as $field) {
            $field_name = $field->name;

            if (in_array($field->type, ['int', 'tinyint', 'decimal'])) {
                $empty_obj->$field_name = ($field->primary_key == 1) ? NEW_ENTRY : 0;
            } else {
                $empty_obj->$field_name = null;
            }
        }

        $empty_obj->customer_name = null;
        $empty_obj->customer_phone = null;
        $empty_obj->customer_identification_type = null;
        $empty_obj->customer_identification = null;
        $empty_obj->employee_name = null;
        $empty_obj->supplier_name = null;

        return $empty_obj;
    }

    public function get_multiple_info(array $assistance_ids): ResultInterface
    {
        $builder = $this->db->table('assistances AS assistances');
        $builder->select('assistances.*, CONCAT(customer.first_name, " ", customer.last_name) AS customer_name, CONCAT(employee.first_name, " ", employee.last_name) AS employee_name, suppliers.company_name AS supplier_name');
        $builder->join('people AS customer', 'customer.person_id = assistances.customer_id', 'left');
        $builder->join('people AS employee', 'employee.person_id = assistances.employee_id', 'left');
        $builder->join('suppliers', 'suppliers.person_id = assistances.supplier_id', 'left');
        $builder->whereIn('assistance_id', $assistance_ids);
        $builder->where('assistances.deleted', 0);
        $builder->orderBy('assistance_id', 'desc');

        return $builder->get();
    }

    public function save_value(array &$assistance_data, int $assistance_id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('assistances');

        if ($assistance_id == NEW_ENTRY || !$this->exists($assistance_id)) {
            if ($builder->insert($assistance_data)) {
                $assistance_data['assistance_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }

        $builder->where('assistance_id', $assistance_id);

        return $builder->update($assistance_data);
    }

    public function delete($assistance_id = null, bool $purge = false): bool
    {
        $builder = $this->db->table('assistances');
        $builder->where('assistance_id', $assistance_id);

        return $builder->update(['deleted' => 1]);
    }

    public function delete_list(array $assistance_ids): bool
    {
        $builder = $this->db->table('assistances');
        $builder->whereIn('assistance_id', $assistance_ids);

        return $builder->update(['deleted' => 1]);
    }

    public function get_search_suggestions(string $search, int $limit = 25): array
    {
        $suggestions = [];

        $builder = $this->db->table('assistances AS assistances');
        $builder->join('people AS customer', 'customer.person_id = assistances.customer_id', 'left');
        $builder->join('suppliers', 'suppliers.person_id = assistances.supplier_id', 'left');
        $builder->groupStart();
        $builder->like('assistances.item_name', $search);
        $builder->orLike('customer.first_name', $search);
        $builder->orLike('customer.last_name', $search);
        $builder->orLike('CONCAT(customer.first_name, " ", customer.last_name)', $search);
        $builder->orLike('suppliers.company_name', $search);
        $builder->orLike('assistances.assistance_id', $search);
        $builder->groupEnd();
        $builder->where('assistances.deleted', 0);
        $builder->orderBy('assistances.assistance_id', 'desc');

        foreach ($builder->get()->getResult() as $row) {
            $suggestions[] = ['label' => "$row->assistance_id - $row->item_name"];
        }

        if (count($suggestions) > $limit) {
            $suggestions = array_slice($suggestions, 0, $limit);
        }

        return $suggestions;
    }

    public function get_found_rows(string $search): int
    {
        return $this->search($search, 0, 0, 'assistances.assistance_id', 'desc', true);
    }

    public function search(string $search, ?int $rows = 0, ?int $limit_from = 0, ?string $sort = 'assistances.assistance_id', ?string $order = 'desc', ?bool $count_only = false)
    {
        if ($rows == null) $rows = 0;
        if ($limit_from == null) $limit_from = 0;
        if ($sort == null) $sort = 'assistances.assistance_id';
        if ($order == null) $order = 'desc';
        if ($count_only == null) $count_only = false;

        $builder = $this->db->table('assistances AS assistances');

        if ($count_only) {
            $builder->select('COUNT(assistances.assistance_id) as count');
        } else {
            $builder->select('assistances.*, CONCAT(customer.first_name, " ", customer.last_name) AS customer_name, CONCAT(employee.first_name, " ", employee.last_name) AS employee_name, suppliers.company_name AS supplier_name');
        }

        $builder->join('people AS customer', 'customer.person_id = assistances.customer_id', 'left');
        $builder->join('people AS employee', 'employee.person_id = assistances.employee_id', 'left');
        $builder->join('suppliers', 'suppliers.person_id = assistances.supplier_id', 'left');
        $builder->groupStart();
        $builder->like('assistances.item_name', $search);
        $builder->orLike('customer.first_name', $search);
        $builder->orLike('customer.last_name', $search);
        $builder->orLike('CONCAT(customer.first_name, " ", customer.last_name)', $search);
        $builder->orLike('suppliers.company_name', $search);
        $builder->orLike('assistances.assistance_id', $search);
        $builder->orLike('assistances.status', $search);
        $builder->groupEnd();
        $builder->where('assistances.deleted', 0);

        if ($count_only) {
            return $builder->get()->getRow()->count;
        }

        $builder->orderBy($sort, $order);

        if ($rows > 0) {
            $builder->limit($rows, $limit_from);
        }

        return $builder->get();
    }
}
