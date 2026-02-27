<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;
use stdClass;

/**
 * Service_ticket class
 */
class Service_ticket extends Model
{
    protected $table = 'service_tickets';
    protected $primaryKey = 'ticket_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'customer_id',
        'employee_id_receiver',
        'employee_id_technician',
        'device_name',
        'issue_description',
        'status',
        'notes',
        'estimated_price',
        'deleted'
    ];

    /**
     * Determines if a given ticket_id exists
     */
    public function exists(int $ticket_id): bool
    {
        $builder = $this->db->table('service_tickets');
        $builder->where('ticket_id', $ticket_id);
        $builder->where('deleted', 0);

        return ($builder->get()->getNumRows() == 1);
    }

    /**
     * Gets total of rows
     */
    public function get_total_rows(): int
    {
        $builder = $this->db->table('service_tickets');
        $builder->where('deleted', 0);

        return $builder->countAllResults();
    }

    /**
     * Gets information about a particular service ticket
     */
    public function get_info(int $ticket_id): object
    {
        $builder = $this->db->table('service_tickets AS service_tickets');
        $builder->select('service_tickets.*, CONCAT(customer.first_name, " ", customer.last_name) AS customer_name, customer.phone_number AS customer_phone, customer.identification_type AS customer_identification_type, customer.identification AS customer_identification, CONCAT(receiver.first_name, " ", receiver.last_name) AS receiver_name, CONCAT(technician.first_name, " ", technician.last_name) AS technician_name');
        $builder->join('people AS customer', 'customer.person_id = service_tickets.customer_id', 'left');
        $builder->join('people AS receiver', 'receiver.person_id = service_tickets.employee_id_receiver', 'left');
        $builder->join('people AS technician', 'technician.person_id = service_tickets.employee_id_technician', 'left');
        $builder->where('ticket_id', $ticket_id);

        $query = $builder->get();

        if ($query->getNumRows() == 1) {
            return $query->getRow();
        }

        return $this->getEmptyObject('service_tickets');
    }

    /**
     * Initializes an empty object based on database definitions
     */
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
        $empty_obj->receiver_name = null;
        $empty_obj->technician_name = null;

        return $empty_obj;
    }

    /**
     * Gets information about multiple service tickets
     */
    public function get_multiple_info(array $ticket_ids): ResultInterface
    {
        $builder = $this->db->table('service_tickets AS service_tickets');
        $builder->select('service_tickets.*, CONCAT(customer.first_name, " ", customer.last_name) AS customer_name, CONCAT(receiver.first_name, " ", receiver.last_name) AS receiver_name, CONCAT(technician.first_name, " ", technician.last_name) AS technician_name');
        $builder->join('people AS customer', 'customer.person_id = service_tickets.customer_id', 'left');
        $builder->join('people AS receiver', 'receiver.person_id = service_tickets.employee_id_receiver', 'left');
        $builder->join('people AS technician', 'technician.person_id = service_tickets.employee_id_technician', 'left');
        $builder->whereIn('ticket_id', $ticket_ids);
        $builder->where('service_tickets.deleted', 0);
        $builder->orderBy('ticket_id', 'desc');

        return $builder->get();
    }

    /**
     * Inserts or updates a service ticket
     */
    public function save_value(array &$ticket_data, int $ticket_id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('service_tickets');

        if ($ticket_id == NEW_ENTRY || !$this->exists($ticket_id)) {
            if ($builder->insert($ticket_data)) {
                $ticket_data['ticket_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }

        $builder->where('ticket_id', $ticket_id);

        return $builder->update($ticket_data);
    }

    /**
     * Deletes one service ticket (soft delete)
     */
    public function delete($ticket_id = null, bool $purge = false): bool
    {
        $builder = $this->db->table('service_tickets');
        $builder->where('ticket_id', $ticket_id);

        return $builder->update(['deleted' => 1]);
    }

    /**
     * Deletes a list of service tickets (soft delete)
     */
    public function delete_list(array $ticket_ids): bool
    {
        $builder = $this->db->table('service_tickets');
        $builder->whereIn('ticket_id', $ticket_ids);

        return $builder->update(['deleted' => 1]);
    }

    /**
     * Get search suggestions
     */
    public function get_search_suggestions(string $search, int $limit = 25): array
    {
        $suggestions = [];

        $builder = $this->db->table('service_tickets AS service_tickets');
        $builder->join('people AS customer', 'customer.person_id = service_tickets.customer_id', 'left');
        $builder->groupStart();
        $builder->like('service_tickets.device_name', $search);
        $builder->orLike('customer.first_name', $search);
        $builder->orLike('customer.last_name', $search);
        $builder->orLike('CONCAT(customer.first_name, " ", customer.last_name)', $search);
        $builder->orLike('service_tickets.ticket_id', $search);
        $builder->groupEnd();
        $builder->where('service_tickets.deleted', 0);
        $builder->orderBy('service_tickets.ticket_id', 'desc');

        foreach ($builder->get()->getResult() as $row) {
            $suggestions[] = ['label' => "$row->ticket_id - $row->device_name"];
        }

        if (count($suggestions) > $limit) {
            $suggestions = array_slice($suggestions, 0, $limit);
        }

        return $suggestions;
    }

    /**
     * Gets found rows for search
     */
    public function get_found_rows(string $search): int
    {
        return $this->search($search, 0, 0, 'service_tickets.ticket_id', 'desc', true);
    }

    /**
     * Performs a search on service tickets
     */
    public function search(string $search, ?int $rows = 0, ?int $limit_from = 0, ?string $sort = 'service_tickets.ticket_id', ?string $order = 'desc', ?bool $count_only = false)
    {
        if ($rows == null) $rows = 0;
        if ($limit_from == null) $limit_from = 0;
        if ($sort == null) $sort = 'service_tickets.ticket_id';
        if ($order == null) $order = 'desc';
        if ($count_only == null) $count_only = false;

        $builder = $this->db->table('service_tickets AS service_tickets');

        if ($count_only) {
            $builder->select('COUNT(service_tickets.ticket_id) as count');
        } else {
            $builder->select('service_tickets.*, CONCAT(customer.first_name, " ", customer.last_name) AS customer_name, CONCAT(receiver.first_name, " ", receiver.last_name) AS receiver_name, CONCAT(technician.first_name, " ", technician.last_name) AS technician_name');
        }

        $builder->join('people AS customer', 'customer.person_id = service_tickets.customer_id', 'left');
        $builder->join('people AS receiver', 'receiver.person_id = service_tickets.employee_id_receiver', 'left');
        $builder->join('people AS technician', 'technician.person_id = service_tickets.employee_id_technician', 'left');
        $builder->groupStart();
        $builder->like('customer.first_name', $search);
        $builder->orLike('customer.last_name', $search);
        $builder->orLike('CONCAT(customer.first_name, " ", customer.last_name)', $search);
        $builder->orLike('service_tickets.device_name', $search);
        $builder->orLike('service_tickets.issue_description', $search);
        $builder->orLike('service_tickets.ticket_id', $search);
        $builder->groupEnd();
        $builder->where('service_tickets.deleted', 0);

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
