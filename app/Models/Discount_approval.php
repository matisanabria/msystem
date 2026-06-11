<?php

namespace App\Models;

use CodeIgniter\Model;

class Discount_approval extends Model
{
    protected $table         = 'ospos_discount_approvals';
    protected $primaryKey    = 'approval_id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'location_id', 'requested_by', 'discount', 'discount_type',
        'item_name', 'item_price', 'item_quantity',
        'auth_code', 'status', 'created_at', 'expires_at', 'approved_by',
    ];

    public function create_request(int $location_id, int $person_id, float $discount, int $discount_type, string $item_name, float $item_price, float $item_quantity): int
    {
        $this->insert([
            'location_id'   => $location_id,
            'requested_by'  => $person_id,
            'discount'      => $discount,
            'discount_type' => $discount_type,
            'item_name'     => $item_name,
            'item_price'    => $item_price,
            'item_quantity' => $item_quantity,
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->getInsertID();
    }

    public function get_pending_for_locations(array $location_ids): array
    {
        if (empty($location_ids)) {
            return [];
        }

        $this->expire_stale();

        return $this->db->table($this->table . ' a')
            ->join('ospos_people p', 'p.person_id = a.requested_by')
            ->join('ospos_stock_locations l', 'l.location_id = a.location_id')
            ->select('a.*, CONCAT(p.first_name, " ", p.last_name) AS cashier_name, l.location_name')
            ->whereIn('a.location_id', $location_ids)
            ->where('a.status', 'pending')
            ->orderBy('a.created_at', 'ASC')
            ->get()->getResultArray();
    }

    public function get_pending_count_for_locations(array $location_ids): array
    {
        if (empty($location_ids)) {
            return ['count' => 0, 'ids' => []];
        }

        $this->expire_stale();

        $rows = $this->db->table($this->table)
            ->select('approval_id')
            ->whereIn('location_id', $location_ids)
            ->where('status', 'pending')
            ->get()->getResultArray();

        return [
            'count' => count($rows),
            'ids'   => array_column($rows, 'approval_id'),
        ];
    }

    public function approve(int $approval_id, int $approver_id): ?string
    {
        $row = $this->find($approval_id);
        if (!$row || $row['status'] !== 'pending') {
            return null;
        }

        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $this->update($approval_id, [
            'auth_code'   => $code,
            'status'      => 'approved',
            'expires_at'  => date('Y-m-d H:i:s', strtotime('+10 minutes')),
            'approved_by' => $approver_id,
        ]);

        return $code;
    }

    public function reject(int $approval_id): void
    {
        $this->update($approval_id, ['status' => 'expired']);
    }

    public function check_code(int $approval_id, string $code, float $discount, int $discount_type, int $requested_by): bool
    {
        $row = $this->find($approval_id);

        return $row
            && $row['status'] === 'approved'
            && $row['auth_code'] === $code
            && (int)$row['requested_by'] === $requested_by
            && abs((float)$row['discount'] - $discount) < 0.005
            && (int)$row['discount_type'] === $discount_type
            && strtotime($row['expires_at']) >= time();
    }

    public function verify(int $approval_id, string $code, float $discount, int $discount_type, int $requested_by): bool
    {
        if (!$this->check_code($approval_id, $code, $discount, $discount_type, $requested_by)) {
            return false;
        }

        $this->update($approval_id, ['status' => 'used']);

        return true;
    }

    public function get_poll_status(int $approval_id): ?string
    {
        $row = $this->find($approval_id);
        if (!$row) {
            return null;
        }

        if ($row['status'] === 'approved' && strtotime($row['expires_at']) < time()) {
            $this->update($approval_id, ['status' => 'expired']);
            return 'expired';
        }

        return $row['status'];
    }

    private function expire_stale(): void
    {
        $this->db->table($this->table)
            ->where('status', 'approved')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->update(['status' => 'expired']);
    }
}
