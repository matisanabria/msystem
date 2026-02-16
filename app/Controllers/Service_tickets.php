<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Service_ticket;

use Config\OSPOS;

require_once('Secure_Controller.php');

class Service_tickets extends Secure_Controller
{
    private Service_ticket $service_ticket;
    private Customer $customer;
    private array $config;

    public function __construct()
    {
        parent::__construct('service_tickets');

        $this->service_ticket = model(Service_ticket::class);
        $this->customer = model(Customer::class);
        $this->config = config(OSPOS::class)->settings;
    }

    /**
     * @return void
     */
    public function getIndex(): void
    {
        $data['table_headers'] = get_service_tickets_manage_table_headers();

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

        $tickets = $this->service_ticket->search($search, $limit, $offset, $sort, $order);
        $total_rows = $this->service_ticket->get_found_rows($search);
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

        // Build customers dropdown
        $customers = ['' => lang('Common.none_selected_text')];
        foreach ($this->customer->get_all()->getResult() as $row) {
            $customers[$row->person_id] = "$row->first_name $row->last_name";
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

        $data['ticket_info'] = $ticket_info;
        $data['customers'] = $customers;
        $data['employees'] = $employees;
        $data['statuses'] = $statuses;
        $data['selected_customer'] = $ticket_info->customer_id ?? '';
        $data['selected_receiver'] = $ticket_info->employee_id_receiver ?? '';
        $data['selected_technician'] = $ticket_info->employee_id_technician ?? '';
        $data['selected_status'] = $ticket_info->status ?? 'received';

        echo view('service_tickets/form', $data);
    }

    /**
     * @param int $ticket_id
     * @return void
     */
    public function postSave(int $ticket_id = NEW_ENTRY): void
    {
        $ticket_data = [
            'customer_id'           => empty($this->request->getPost('customer_id')) ? null : intval($this->request->getPost('customer_id')),
            'employee_id_receiver'  => intval($this->request->getPost('employee_id_receiver')),
            'employee_id_technician' => empty($this->request->getPost('employee_id_technician')) ? null : intval($this->request->getPost('employee_id_technician')),
            'device_name'           => $this->request->getPost('device_name'),
            'issue_description'     => $this->request->getPost('issue_description'),
            'status'                => $this->request->getPost('status'),
            'notes'                 => $this->request->getPost('notes'),
            'estimated_price'       => parse_decimals($this->request->getPost('estimated_price')),
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
     * @return void
     */
    public function getSuggest(): void
    {
        $search = $this->request->getGet('term');
        $suggestions = $this->service_ticket->get_search_suggestions($search);

        echo json_encode($suggestions);
    }
}
