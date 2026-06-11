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
        $location_ids = array_keys($this->stock_location->get_allowed_locations('sales'));
        $pending      = $this->approval_model->get_pending_for_locations($location_ids);

        $data = $this->global_view_data + [
            'pending' => $pending,
        ];

        echo view('discount_approvals/index', $data);
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
     * AJAX: returns full pending rows as JSON for dynamic table rendering (no page reload).
     */
    public function getPendingRows(): void
    {
        $location_ids = array_keys($this->stock_location->get_allowed_locations('sales'));
        $rows         = $this->approval_model->get_pending_for_locations($location_ids);

        $result = array_map(function ($row) {
            $price    = (float)$row['item_price'];
            $qty      = (float)$row['item_quantity'];
            $discount = (float)$row['discount'];
            $dtype    = (int)$row['discount_type'];
            $subtotal = $price * $qty;

            if ($dtype === 1) {
                $disc_amount = $discount * $qty;
                $disc_label  = to_currency($discount) . ' c/u';
            } else {
                $disc_amount = $subtotal * $discount / 100;
                $disc_label  = number_format($discount, 1) . '%';
            }

            $final = $subtotal - $disc_amount;

            return [
                'approval_id'   => (int)$row['approval_id'],
                'created_ts'    => strtotime($row['created_at']),
                'time_label'    => date('H:i:s', strtotime($row['created_at'])),
                'cashier_name'  => $row['cashier_name'],
                'location_name' => $row['location_name'],
                'item_name'     => $row['item_name'] ?: '—',
                'price_fmt'     => to_currency($price),
                'qty_fmt'       => number_format($qty, 0),
                'subtotal_fmt'  => to_currency($subtotal),
                'disc_label'    => $disc_label,
                'final_fmt'     => to_currency($final),
                'savings_fmt'   => to_currency($disc_amount),
            ];
        }, $rows);

        echo json_encode(['rows' => $result]);
    }
}
