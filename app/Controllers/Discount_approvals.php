<?php

namespace App\Controllers;

use App\Models\Discount_approval;
use App\Models\Stock_location;

class Discount_approvals extends Secure_Controller
{
    private Discount_approval $approval_model;
    private Stock_location $stock_location;

    public function __construct()
    {
        parent::__construct('discount_approvals');

        $this->approval_model = model(Discount_approval::class);
        $this->stock_location = model(Stock_location::class);
    }

    public function getIndex(): void
    {
        // the rows come from pendingRows (JSON), so the page shows the same list as its refreshes
        echo view('discount_approvals/index', $this->global_view_data);
    }

    /**
     * AJAX: admin approves a pending discount request. Returns the 4-digit code.
     */
    public function postApprove(): void
    {
        $person_id   = $this->employee->get_logged_in_employee_info()->person_id;
        $approval_id = (int)$this->request->getPost('approval_id');
        $code        = $this->approval_model->approve($approval_id, $person_id);

        if ($code === null) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada o ya procesada']);
            return;
        }

        echo json_encode(['success' => true, 'code' => $code]);
    }

    /**
     * AJAX: admin rejects a pending discount request.
     */
    public function postReject(): void
    {
        $approval_id = (int)$this->request->getPost('approval_id');
        $this->approval_model->reject($approval_id);

        echo json_encode(['success' => true]);
    }

    /**
     * AJAX: returns pending count + IDs for the menubar badge and toast notifications.
     */
    public function getPendingCount(): void
    {
        $location_ids = array_keys($this->stock_location->get_allowed_locations('sales'));
        $result       = $this->approval_model->get_pending_count_for_locations($location_ids);

        echo json_encode($result);
    }

    /**
     * Seconds a code stays usable after approval; pending requests older than this are shown as expired (UI only).
     */
    private const CODE_LIFETIME = 600;

    /**
     * What a request changes, for display: label, before -> after prices and the difference.
     */
    private function describe(array $row): array
    {
        $price = (float)$row['item_price'];
        $qty   = (float)$row['item_quantity'];

        if (($row['request_type'] ?? 'discount') === 'price_type') {
            $labels = [1 => 'Mayorista', 2 => 'Revendedor'];
            $prices = $this->approval_model->get_item_prices($row['item_name'], (int)$row['location_id']);
            $old    = $prices ? (float)$prices['unit_price'] : null;   // the stored request only has the new price
            $new    = $price;

            $change_label  = 'Venta → ' . ($labels[(int)$row['price_type']] ?? '?');
            $change_prices = ($old !== null ? to_currency($old) . ' → ' : '→ ') . to_currency($new);
            $diff          = $old !== null ? ($new - $old) * $qty : null;
            $pct           = $old ? ($new - $old) / $old * 100 : null;
        } else {
            $discount = (float)$row['discount'];
            $subtotal = $price * $qty;
            $amount   = (int)$row['discount_type'] === 1 ? $discount * $qty : $subtotal * $discount / 100;

            $change_label  = 'Descuento ' . ((int)$row['discount_type'] === 1 ? to_currency($discount) . ' c/u' : number_format($discount, 1, ',', '.') . '%');
            $change_prices = to_currency($subtotal) . ' → ' . to_currency($subtotal - $amount);
            $diff          = -$amount;
            $pct           = $subtotal ? -$amount / $subtotal * 100 : null;
        }

        if ($diff === null) {
            $diff_text  = '—';
            $diff_class = 'none';
        } elseif (abs($diff) < 0.5) {
            $diff_text  = 'Sin diferencia de precio';
            $diff_class = 'none';
        } else {
            $sign       = $diff < 0 ? '−' : '+';
            $diff_text  = $sign . to_currency(abs($diff)) . ($pct !== null ? ' (' . $sign . number_format(abs($pct), 1, ',', '.') . '%)' : '');
            $diff_class = $diff < 0 ? 'down' : 'up';
        }

        return [
            'change_label'  => $change_label,
            'change_prices' => $change_prices,
            'diff_text'     => $diff_text,
            'diff_class'    => $diff_class,
        ];
    }

    /**
     * AJAX: returns full pending rows as JSON for dynamic table rendering (no page reload).
     */
    public function getPendingRows(): void
    {
        $location_ids = array_keys($this->stock_location->get_allowed_locations('sales'));
        $rows         = $this->approval_model->get_pending_for_locations($location_ids);
        $now          = time();

        $result = array_map(function ($row) use ($now) {
            $created = strtotime($row['created_at']);

            return [
                'approval_id'   => (int)$row['approval_id'],
                'created_ts'    => $created,
                'time_label'    => date('H:i', $created),
                'cashier_name'  => $row['cashier_name'],
                'location_name' => $row['location_name'],
                'item_name'     => $row['item_name'] ?: '—',
                'qty_fmt'       => number_format((float)$row['item_quantity'], 0),
                'expired'       => ($now - $created) > self::CODE_LIFETIME,
            ] + $this->describe($row);
        }, $rows);

        echo json_encode(['rows' => $result, 'now' => $now]);
    }

    /**
     * AJAX: last resolved requests for the history tab (read-only).
     */
    public function getHistory(): void
    {
        $location_ids = array_keys($this->stock_location->get_allowed_locations('sales'));
        $rows         = $this->approval_model->get_recent_resolved($location_ids, 50);
        $now          = time();

        $result = array_map(function ($row) use ($now) {
            $created = strtotime($row['created_at']);

            // pending requests never expire on their own, so "expired" without an approver means it was rejected
            if ($row['status'] === 'used') {
                $result_key = 'approved';
            } elseif ($row['status'] === 'approved') {
                $result_key = strtotime($row['expires_at']) < $now ? 'expired' : 'approved';
            } else {
                $result_key = $row['approved_by'] ? 'expired' : 'rejected';
            }

            return [
                'approval_id'   => (int)$row['approval_id'],
                'date_label'    => date('d/m H:i', $created),
                'cashier_name'  => $row['cashier_name'],
                'location_name' => $row['location_name'],
                'item_name'     => $row['item_name'] ?: '—',
                'qty_fmt'       => number_format((float)$row['item_quantity'], 0),
                'resolver'      => $row['approver_name'] ?: '—',
                'result'        => $result_key,
            ] + $this->describe($row);
        }, $rows);

        echo json_encode(['rows' => $result]);
    }
}
