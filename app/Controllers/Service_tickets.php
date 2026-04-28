<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Service_ticket;
use App\Models\Stock_location;

use Config\OSPOS;

require_once('Secure_Controller.php');

class Service_tickets extends Secure_Controller
{
    private Service_ticket $service_ticket;
    private Customer $customer;
    private Stock_location $stock_location;
    private array $config;

    public function __construct()
    {
        parent::__construct('service_tickets');

        $this->service_ticket = model(Service_ticket::class);
        $this->customer = model(Customer::class);
        $this->stock_location = model(Stock_location::class);
        $this->config = config(OSPOS::class)->settings;
    }

    /**
     * @return void
     */
    public function getIndex(): void
    {
        $data['table_headers'] = get_service_tickets_manage_table_headers();

        $allowed = $this->stock_location->get_allowed_locations('service_tickets');
        $data['stock_locations'] = $allowed;
        $data['show_location_filter'] = count($allowed) > 1;

        echo view('service_tickets/manage', $data);
    }

    /**
     * Returns service tickets table data rows. Called via AJAX.
     * @return void
     */
    public function getSearch(): void
    {
        $search = $this->request->getGet('search');
        $limit = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort = $this->sanitizeSortColumn(service_ticket_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'service_tickets.ticket_id');
        $order = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $allowed_location_ids = array_keys($this->stock_location->get_allowed_locations('service_tickets'));
        $selected_location    = $this->request->getGet('location_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'all';

        if ($selected_location !== 'all' && in_array((int)$selected_location, $allowed_location_ids)) {
            $location_ids = [(int)$selected_location];
        } else {
            $location_ids = $allowed_location_ids ?: null;
        }

        $tickets = $this->service_ticket->search($search, $limit, $offset, $sort, $order, false, $location_ids);
        $total_rows = $this->service_ticket->get_found_rows($search, $location_ids);
        $data_rows = [];

        foreach ($tickets->getResult() as $ticket) {
            $data_rows[] = get_service_ticket_data_row($ticket);
        }

        echo json_encode(['total' => $total_rows, 'rows' => $data_rows]);
    }

    /**
     * @param string $ticket_ids
     * @return void
     */
    public function getRow(string $ticket_ids): void
    {
        $ticket_infos = $this->service_ticket->get_multiple_info(explode(':', $ticket_ids));

        $result = [];

        foreach ($ticket_infos->getResult() as $ticket_info) {
            $result[$ticket_info->ticket_id] = get_service_ticket_data_row($ticket_info);
        }

        echo json_encode($result);
    }

    /**
     * @param int $ticket_id
     * @return void
     */
    public function getView(int $ticket_id = NEW_ENTRY): void
    {
        $ticket_info = $this->service_ticket->get_info($ticket_id);

        // Customer name for existing tickets
        $selected_customer_id = $ticket_info->customer_id ?? null;
        $data['selected_customer_name'] = '';
        if ($selected_customer_id) {
            $customer_info = $this->customer->get_info($selected_customer_id);
            if ($customer_info) {
                $data['selected_customer_name'] = trim($customer_info->first_name . ' ' . $customer_info->last_name);
            }
        }

        // Build employees dropdown
        $employees = ['' => lang('Common.none_selected_text')];
        foreach ($this->employee->get_all()->getResult() as $row) {
            $employees[$row->person_id] = "$row->first_name $row->last_name";
        }

        $statuses = [
            'received'  => lang('Service_tickets.status_received'),
            'waiting'   => lang('Service_tickets.status_waiting'),
            'in_repair' => lang('Service_tickets.status_in_repair'),
            'repaired'  => lang('Service_tickets.status_repaired'),
        ];

        $allowed = $this->stock_location->get_allowed_locations('service_tickets');

        if ($ticket_id === NEW_ENTRY) {
            $ticket_location_id = (int)array_key_first($allowed);
        } else {
            $ticket_location_id = (int)($ticket_info->location_id ?? array_key_first($allowed));
        }

        $data['ticket_info']          = $ticket_info;
        $data['employees']            = $employees;
        $data['statuses']             = $statuses;
        $data['selected_customer']    = $ticket_info->customer_id ?? '';
        $data['selected_receiver']    = $ticket_info->employee_id_receiver ?? '';
        $data['selected_technician']  = $ticket_info->employee_id_technician ?? '';
        $data['selected_status']      = $ticket_info->status ?? 'received';
        $data['stock_locations']      = $allowed;
        $data['show_location_select'] = count($allowed) > 1;
        $data['ticket_location_id']   = $ticket_location_id;

        echo view('service_tickets/form', $data);
    }

    /**
     * @param int $ticket_id
     * @return void
     */
    public function postSave(int $ticket_id = NEW_ENTRY): void
    {
        $allowed = $this->stock_location->get_allowed_locations('service_tickets');
        $post_location = $this->request->getPost('location_id', FILTER_SANITIZE_NUMBER_INT);

        if ($ticket_id !== NEW_ENTRY) {
            $existing = $this->service_ticket->get_info($ticket_id);
            $location_id = (int)($existing->location_id ?? array_key_first($allowed));
        } else {
            $location_id = ($post_location !== null && isset($allowed[(int)$post_location]))
                ? (int)$post_location
                : (int)array_key_first($allowed);
        }

        $ticket_data = [
            'customer_id'            => empty($this->request->getPost('customer_id')) ? null : intval($this->request->getPost('customer_id')),
            'employee_id_receiver'   => intval($this->request->getPost('employee_id_receiver')),
            'employee_id_technician' => empty($this->request->getPost('employee_id_technician')) ? null : intval($this->request->getPost('employee_id_technician')),
            'device_name'            => $this->request->getPost('device_name'),
            'issue_description'      => $this->request->getPost('issue_description'),
            'status'                 => $this->request->getPost('status'),
            'notes'                  => $this->request->getPost('notes'),
            'estimated_price'        => parse_decimals($this->request->getPost('estimated_price')),
            'location_id'            => $location_id,
        ];

        if ($this->service_ticket->save_value($ticket_data, $ticket_id)) {
            $message = $ticket_id == NEW_ENTRY
                ? lang('Service_tickets.successful_adding')
                : lang('Service_tickets.successful_updating');

            echo json_encode([
                'success' => true,
                'message' => $message . ' ' . $ticket_data['device_name'],
                'id'      => $ticket_data['ticket_id'] ?? $ticket_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => lang('Service_tickets.error_adding_updating') . ' ' . $ticket_data['device_name'],
                'id'      => NEW_ENTRY
            ]);
        }
    }

    /**
     * @return void
     */
    public function postDelete(): void
    {
        $tickets_to_delete = $this->request->getPost('ids');

        if ($this->service_ticket->delete_list($tickets_to_delete)) {
            $message = lang('Service_tickets.successful_deleted') . ' ' . count($tickets_to_delete) . ' ' . lang('Service_tickets.one_or_multiple');
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => lang('Service_tickets.cannot_be_deleted')]);
        }
    }

    /**
     * Renders the printable receipt (2 copies) for a service ticket.
     * @param int $ticket_id
     * @return void
     */
    public function getReceipt(int $ticket_id): void
    {
        $ticket = $this->service_ticket->get_info($ticket_id);
        $data['ticket'] = $ticket;
        $data['config'] = $this->config;
        $data['ticket_date'] = to_datetime(strtotime($ticket->created_at));

        echo view('service_tickets/receipt', $data);
    }

    /**
     * @return void
     */
    public function getSuggest(): void
    {
        $search = $this->request->getGet('term');
        $suggestions = $this->service_ticket->get_search_suggestions($search);

        echo json_encode($suggestions);
    }
}
