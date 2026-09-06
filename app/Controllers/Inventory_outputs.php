<?php

namespace App\Controllers;

use App\Models\Activity_log;
use App\Models\Inventory;
use App\Models\Inventory_output;
use App\Models\Item;
use App\Models\Item_quantity;
use App\Models\Stock_location;
use CodeIgniter\Database\BaseConnection;

class Inventory_outputs extends Secure_Controller
{
    private Inventory_output $inventory_output;
    private Item $item;
    private Item_quantity $item_quantity;
    private Inventory $inventory;
    private Stock_location $stock_location;
    private BaseConnection $db;

    public function __construct()
    {
        parent::__construct('inventory_output');

        $this->inventory_output = model(Inventory_output::class);
        $this->item             = model(Item::class);
        $this->item_quantity    = model(Item_quantity::class);
        $this->inventory        = model(Inventory::class);
        $this->stock_location   = model(Stock_location::class);
        $this->db               = db_connect();
    }

    public function getIndex(): void
    {
        $data['table_headers'] = get_inventory_output_manage_table_headers();
        $data['reasons'] = $this->_reason_options();

        $allowed = $this->stock_location->get_allowed_locations('inventory_output');
        $data['stock_locations'] = $allowed;
        $data['show_location_filter'] = count($allowed) > 1;

        echo view('inventory_outputs/manage', $data);
    }

    public function getSearch(): void
    {
        $search = $this->request->getGet('search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $limit  = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort   = $this->sanitizeSortColumn(inventory_output_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'output_id');
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'desc';

        $filters = [
            'start_date' => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'end_date'   => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'reason'     => $this->request->getGet('reason', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '',
        ];

        $allowed_location_ids = array_keys($this->stock_location->get_allowed_locations('inventory_output'));
        $selected_location    = $this->request->getGet('location_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'all';

        if ($selected_location !== 'all' && in_array((int)$selected_location, $allowed_location_ids)) {
            $filters['location_ids'] = [(int)$selected_location];
        } else {
            $filters['location_ids'] = $allowed_location_ids;
        }

        $rows = $this->inventory_output->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->inventory_output->get_found_rows($search, $filters);

        $data_rows = [];
        foreach ($rows->getResult() as $row) {
            $data_rows[] = get_inventory_output_data_row($row);
        }

        echo json_encode(['total' => $total_rows, 'rows' => $data_rows]);
    }

    public function getRow(int $row_id): void
    {
        $row = $this->inventory_output->get_info($row_id);
        echo json_encode(get_inventory_output_data_row($row));
    }

    public function getView(int $output_id = NEW_ENTRY): void
    {
        $data['output_info'] = $this->inventory_output->get_info($output_id);
        $data['reasons'] = $this->_reason_options();
        $data['is_new'] = $output_id === NEW_ENTRY;

        echo view('inventory_outputs/form', $data);
    }

    /**
     * AJAX: item autocomplete restricted to locations this employee can register outputs for.
     *
     * @noinspection PhpUnused
     */
    public function getItemSuggest(): void
    {
        $term = $this->request->getGet('term') ?? '';
        $allowed_location_ids = array_keys($this->stock_location->get_allowed_locations('inventory_output'));

        $suggestions = $this->item->get_search_suggestions(
            $term,
            ['search_custom' => false, 'is_deleted' => false],
            true,
            25
        );

        // get_search_suggestions() isn't location-aware for multiple locations, so filter
        // the resulting item ids down to what this employee is allowed to register outputs for.
        $filtered = [];
        foreach ($suggestions as $suggestion) {
            if (!isset($suggestion['value'])) {
                continue;
            }

            $item_info = $this->item->get_info((int)$suggestion['value']);
            if (!empty($item_info->item_id) && in_array((int)$item_info->location_id, $allowed_location_ids)) {
                $filtered[] = $suggestion;
            }
        }

        echo json_encode($filtered);
    }

    /**
     * AJAX: returns item + stock info to populate the form once an item is selected.
     *
     * @noinspection PhpUnused
     */
    public function getItemInfo(int $item_id): void
    {
        $allowed_location_ids = array_keys($this->stock_location->get_allowed_locations('inventory_output'));
        $item_info = $this->item->get_info($item_id);

        if (empty($item_info->item_id) || $item_info->item_id === NEW_ENTRY || !in_array((int)$item_info->location_id, $allowed_location_ids)) {
            echo json_encode(['success' => false, 'message' => lang('Inventory_outputs.item_not_allowed')]);
            return;
        }

        $quantity = $this->item_quantity->get_item_quantity($item_id, (int)$item_info->location_id);

        echo json_encode([
            'success'       => true,
            'item_id'       => $item_info->item_id,
            'name'          => $item_info->name,
            'item_number'   => $item_info->item_number,
            'location_id'   => (int)$item_info->location_id,
            'location_name' => $this->stock_location->get_location_name((int)$item_info->location_id),
            'quantity'      => (float)$quantity->quantity,
        ]);
    }

    /**
     * @noinspection PhpUnused
     */
    public function postSave(): void
    {
        $person_id = $this->employee->get_logged_in_employee_info()->person_id;

        $item_id  = (int)$this->request->getPost('item_id');
        $quantity = parse_decimals((string)($this->request->getPost('quantity') ?? ''));
        $reason   = (string)($this->request->getPost('reason', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
        $comment  = trim((string)$this->request->getPost('comment'));

        if ($item_id <= 0 || $quantity === false || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => lang('Inventory_outputs.invalid_data')]);
            return;
        }

        if (!array_key_exists($reason, Inventory_output::REASONS)) {
            echo json_encode(['success' => false, 'message' => lang('Inventory_outputs.reason_required')]);
            return;
        }

        if ($reason === 'other' && $comment === '') {
            echo json_encode(['success' => false, 'message' => lang('Inventory_outputs.comment_required_other')]);
            return;
        }

        $allowed_location_ids = array_keys($this->stock_location->get_allowed_locations('inventory_output'));
        $item_info = $this->item->get_info($item_id);

        if (empty($item_info->item_id) || $item_info->item_id === NEW_ENTRY || !in_array((int)$item_info->location_id, $allowed_location_ids)) {
            echo json_encode(['success' => false, 'message' => lang('Inventory_outputs.item_not_allowed')]);
            return;
        }

        $location_id = (int)$item_info->location_id;

        // Validate available stock BEFORE touching anything — Item_quantity has no such guard
        // of its own and would happily let quantity go negative.
        $current_quantity = (float)$this->item_quantity->get_item_quantity($item_id, $location_id)->quantity;

        if ($quantity > $current_quantity) {
            echo json_encode([
                'success' => false,
                'message' => lang('Inventory_outputs.insufficient_stock', [to_quantity_decimals($current_quantity)]),
            ]);
            return;
        }

        $reason_label = lang('Inventory_outputs.reason_' . $reason);
        $trans_comment = lang('Inventory_outputs.kardex_comment', [$reason_label]) . ($comment !== '' ? ' — ' . $comment : '');

        $this->db->transStart();

        // 1) Stock: same decimal-safe update Sale::save_value() uses for a completed sale
        //    (Item_quantity::change_quantity() takes an int and would truncate fractional quantities).
        $this->item_quantity->save_value(
            ['quantity' => $current_quantity - $quantity, 'item_id' => $item_id, 'location_id' => $location_id],
            $item_id,
            $location_id
        );

        // 2) Kardex — same table/pattern sales and receiving already write to.
        $trans_id = $this->inventory->insert([
            'trans_items'     => $item_id,
            'trans_user'      => $person_id,
            'trans_comment'   => $trans_comment,
            'trans_inventory' => -1 * $quantity,
            'trans_location'  => $location_id,
        ]);

        // 3) This module's own record, cross-referencing the kardex row above.
        $output_data = [
            'item_id'            => $item_id,
            'location_id'        => $location_id,
            'quantity'           => $quantity,
            'reason'             => $reason,
            'comment'            => $reason === 'other' ? $comment : null,
            'inventory_trans_id' => $trans_id ?: null,
            'person_id'          => $person_id,
            'created_at'         => date('Y-m-d H:i:s'),
            'deleted'            => 0,
        ];

        $output_id = $this->inventory_output->insert($output_data);

        // 4) Activity/audit log.
        $activity_log = model(Activity_log::class);
        $desc = lang('Inventory_outputs.activity_log_desc', [
            $item_info->name,
            to_quantity_decimals($quantity),
            $reason_label,
        ]);
        $activity_log->log('inventory_output', $desc, $person_id, $location_id, $output_id, $this->request->getIPAddress());

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            echo json_encode(['success' => false, 'message' => lang('Inventory_outputs.error_adding')]);
            return;
        }

        echo json_encode(['success' => true, 'message' => lang('Inventory_outputs.successful_adding'), 'id' => $output_id]);
    }

    /**
     * @noinspection PhpUnused
     */
    public function postDelete(): void
    {
        $ids = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [];

        $success = true;
        foreach ($ids as $id) {
            $success = $success && $this->inventory_output->soft_delete((int)$id);
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => lang('Inventory_outputs.successful_deleted'), 'ids' => $ids]);
        } else {
            echo json_encode(['success' => false, 'message' => lang('Inventory_outputs.cannot_be_deleted'), 'ids' => $ids]);
        }
    }

    private function _reason_options(): array
    {
        $options = [];
        foreach (array_keys(Inventory_output::REASONS) as $reason) {
            $options[$reason] = lang('Inventory_outputs.reason_' . $reason);
        }

        return $options;
    }
}
