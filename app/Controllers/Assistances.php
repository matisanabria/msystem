<?php

namespace App\Controllers;

use App\Models\Assistance;
use App\Models\Customer;
use App\Models\Item_quantity;
use App\Models\Stock_location;
use App\Models\Supplier;
use CodeIgniter\Database\BaseConnection;

use Config\OSPOS;

require_once('Secure_Controller.php');

class Assistances extends Secure_Controller
{
    private Assistance $assistance;
    private Customer $customer;
    private Supplier $supplier;
    private Item_quantity $item_quantity;
    private Stock_location $stock_location;
    private BaseConnection $db;
    private array $config;

    public function __construct()
    {
        parent::__construct('assistances');

        $this->assistance = model(Assistance::class);
        $this->customer = model(Customer::class);
        $this->supplier = model(Supplier::class);
        $this->item_quantity = model(Item_quantity::class);
        $this->stock_location = model(Stock_location::class);
        $this->db = db_connect();
        $this->config = config(OSPOS::class)->settings;
    }

    public function getIndex(): void
    {
        $data['table_headers'] = get_assistances_manage_table_headers();

        echo view('assistances/manage', $data);
    }

    public function getSearch(): void
    {
        $search = $this->request->getGet('search');
        $limit = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort = $this->sanitizeSortColumn(assistance_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'assistances.assistance_id');
        $order = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $assistances = $this->assistance->search($search, $limit, $offset, $sort, $order);
        $total_rows = $this->assistance->get_found_rows($search);
        $data_rows = [];

        foreach ($assistances->getResult() as $assistance) {
            $data_rows[] = get_assistance_data_row($assistance);
        }

        echo json_encode(['total' => $total_rows, 'rows' => $data_rows]);
    }

    public function getRow(string $assistance_ids): void
    {
        $assistance_infos = $this->assistance->get_multiple_info(explode(':', $assistance_ids));

        $result = [];

        foreach ($assistance_infos->getResult() as $assistance_info) {
            $result[$assistance_info->assistance_id] = get_assistance_data_row($assistance_info);
        }

        echo json_encode($result);
    }

    public function getView(int $assistance_id = NEW_ENTRY): void
    {
        $assistance_info = $this->assistance->get_info($assistance_id);

        // Customer name for existing records
        $selected_customer_id = $assistance_info->customer_id ?? null;
        $data['selected_customer_name'] = '';
        if ($selected_customer_id) {
            $customer_info = $this->customer->get_info($selected_customer_id);
            if ($customer_info) {
                $data['selected_customer_name'] = trim($customer_info->first_name . ' ' . $customer_info->last_name);
            }
        }

        // Supplier name for existing records
        $data['selected_supplier_name'] = $assistance_info->supplier_name ?? '';

        // Build employees dropdown
        $employees = ['' => lang('Common.none_selected_text')];
        foreach ($this->employee->get_all()->getResult() as $row) {
            $employees[$row->person_id] = "$row->first_name $row->last_name";
        }

        $statuses = [
            'received'              => lang('Assistances.status_received'),
            'sent_to_supplier'      => lang('Assistances.status_sent_to_supplier'),
            'in_repair'             => lang('Assistances.status_in_repair'),
            'returned'              => lang('Assistances.status_returned'),
            'delivered_to_customer' => lang('Assistances.status_delivered_to_customer'),
        ];

        // Default employee to logged-in user for new records
        $selected_employee = $assistance_info->employee_id ?? '';
        if ($assistance_id == NEW_ENTRY) {
            $selected_employee = $this->employee->get_logged_in_employee_info()->person_id;
        }

        $data['assistance_info'] = $assistance_info;
        $data['employees'] = $employees;
        $data['statuses'] = $statuses;
        $data['selected_customer'] = $assistance_info->customer_id ?? '';
        $data['selected_supplier'] = $assistance_info->supplier_id ?? '';
        $data['selected_employee'] = $selected_employee;
        $data['selected_status'] = $assistance_info->status ?? 'received';

        echo view('assistances/form', $data);
    }

    public function postSave(int $assistance_id = NEW_ENTRY): void
    {
        $affects_stock = $this->request->getPost('affects_stock') ? 1 : 0;
        $new_status = $this->request->getPost('status');
        $item_id = empty($this->request->getPost('item_id')) ? null : intval($this->request->getPost('item_id'));
        $location_id = $this->stock_location->get_default_location_id('items');

        $assistance_data = [
            'item_id'             => $item_id,
            'customer_id'         => empty($this->request->getPost('customer_id')) ? null : intval($this->request->getPost('customer_id')),
            'supplier_id'         => empty($this->request->getPost('supplier_id')) ? null : intval($this->request->getPost('supplier_id')),
            'employee_id'         => intval($this->request->getPost('employee_id')),
            'item_name'           => $this->request->getPost('item_name'),
            'problem_description' => $this->request->getPost('problem_description'),
            'supplier_notes'      => $this->request->getPost('supplier_notes'),
            'resolution'          => $this->request->getPost('resolution'),
            'status'              => $new_status,
            'affects_stock'       => $affects_stock,
            'location_id'         => $location_id,
            'sent_date'           => $this->request->getPost('sent_date') ?: null,
            'return_date'         => $this->request->getPost('return_date') ?: null,
            'delivered_date'      => $this->request->getPost('delivered_date') ?: null,
        ];

        $is_new = ($assistance_id == NEW_ENTRY);
        $old_status = null;
        $existing = null;

        // For new records, handle stock deduction
        if ($is_new && $affects_stock && $item_id) {
            $assistance_data['stock_adjusted'] = 1;
        }

        // For existing records, handle status change to 'returned'
        if (!$is_new) {
            $existing = $this->assistance->get_info($assistance_id);
            $old_status = $existing->status;

            // Stock restore when status changes to 'returned' and stock was previously adjusted
            if ($new_status == 'returned' && $old_status != 'returned' && $existing->stock_adjusted == 1) {
                $assistance_data['stock_adjusted'] = 0;
            }
        }

        if ($this->assistance->save_value($assistance_data, $assistance_id)) {
            // Perform stock adjustments after successful save
            if ($is_new && $affects_stock && $item_id) {
                $this->item_quantity->change_quantity($item_id, $location_id, -1);
                $inventory_data = [
                    'trans_items'     => $item_id,
                    'trans_user'      => intval($this->request->getPost('employee_id')),
                    'trans_comment'   => 'Asistencia #' . ($assistance_data['assistance_id'] ?? $assistance_id) . ' - Stock descontado',
                    'trans_inventory' => -1,
                    'trans_location'  => $location_id
                ];
                $this->db->table('inventory')->insert($inventory_data);
            }

            if (!$is_new && $new_status == 'returned' && $old_status != 'returned' && $existing->stock_adjusted == 1 && $existing->item_id) {
                $this->item_quantity->change_quantity($existing->item_id, $existing->location_id ?? $location_id, 1);
                $inventory_data = [
                    'trans_items'     => $existing->item_id,
                    'trans_user'      => intval($this->request->getPost('employee_id')),
                    'trans_comment'   => 'Asistencia #' . $assistance_id . ' - Stock repuesto',
                    'trans_inventory' => 1,
                    'trans_location'  => $existing->location_id ?? $location_id
                ];
                $this->db->table('inventory')->insert($inventory_data);
            }

            $message = $is_new
                ? lang('Assistances.successful_adding')
                : lang('Assistances.successful_updating');

            echo json_encode([
                'success' => true,
                'message' => $message . ' ' . $assistance_data['item_name'],
                'id'      => $assistance_data['assistance_id'] ?? $assistance_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => lang('Assistances.error_adding_updating') . ' ' . $assistance_data['item_name'],
                'id'      => NEW_ENTRY
            ]);
        }
    }

    public function postDelete(): void
    {
        $assistances_to_delete = $this->request->getPost('ids');

        if ($this->assistance->delete_list($assistances_to_delete)) {
            $message = lang('Assistances.successful_deleted') . ' ' . count($assistances_to_delete) . ' ' . lang('Assistances.one_or_multiple');
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => lang('Assistances.cannot_be_deleted')]);
        }
    }

    public function getReceipt(int $assistance_id): void
    {
        $assistance = $this->assistance->get_info($assistance_id);
        $data['assistance'] = $assistance;
        $data['config'] = $this->config;
        $data['assistance_date'] = to_datetime(strtotime($assistance->created_at));

        echo view('assistances/receipt', $data);
    }

    public function getSuggest(): void
    {
        $search = $this->request->getGet('term');
        $suggestions = $this->assistance->get_search_suggestions($search);

        echo json_encode($suggestions);
    }

    public function getItemInfo(int $item_id): void
    {
        $item = model(\App\Models\Item::class);
        $item_info = $item->get_info($item_id);

        $result = [
            'item_id'       => $item_id,
            'name'          => $item_info->name ?? '',
            'supplier_id'   => $item_info->supplier_id ?? null,
            'supplier_name' => '',
        ];

        if (!empty($item_info->supplier_id)) {
            $supplier_info = $this->supplier->get_info($item_info->supplier_id);
            $result['supplier_name'] = $supplier_info->company_name ?? '';
        }

        echo json_encode($result);
    }
}
