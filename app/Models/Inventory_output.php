<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;
use stdClass;

class Inventory_output extends Model
{
    protected $table         = 'inventory_outputs';
    protected $primaryKey    = 'output_id';
    protected $useTimestamps = false;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'item_id',
        'location_id',
        'quantity',
        'reason',
        'comment',
        'inventory_trans_id',
        'person_id',
        'created_at',
        'deleted',
    ];

    public const REASONS = [
        'internal_use'    => 'internal_use',
        'damaged'         => 'damaged',
        'sample'          => 'sample',
        'loss_theft'      => 'loss_theft',
        'supplier_return' => 'supplier_return',
        'other'           => 'other',
    ];

    public function get_found_rows(string $search, array $filters): int
    {
        return $this->search($search, $filters, 0, 0, 'output_id', 'desc', true);
    }

    /**
     * @return ResultInterface|int
     */
    public function search(string $search, array $filters, ?int $rows = 0, ?int $limit_from = 0, ?string $sort = 'output_id', ?string $order = 'desc', ?bool $count_only = false)
    {
        $rows = $rows ?? 0;
        $limit_from = $limit_from ?? 0;
        $sort = $sort ?? 'output_id';
        $order = $order ?? 'desc';
        $count_only = $count_only ?? false;

        $builder = $this->db->table('inventory_outputs AS inventory_outputs');

        if ($count_only) {
            $builder->select('COUNT(inventory_outputs.output_id) AS count');
        } else {
            $builder->select('
                inventory_outputs.output_id,
                inventory_outputs.item_id,
                inventory_outputs.quantity,
                inventory_outputs.reason,
                inventory_outputs.comment,
                inventory_outputs.created_at,
                inventory_outputs.location_id,
                items.name AS item_name,
                items.item_number AS item_number,
                stock_locations.location_name AS location_name,
                people.first_name AS first_name,
                people.last_name AS last_name
            ');
        }

        $builder->join('items AS items', 'items.item_id = inventory_outputs.item_id', 'LEFT');
        $builder->join('stock_locations AS stock_locations', 'stock_locations.location_id = inventory_outputs.location_id', 'LEFT');
        $builder->join('people AS people', 'people.person_id = inventory_outputs.person_id', 'LEFT');

        $builder->where('inventory_outputs.deleted', 0);

        if (!empty($search)) {
            $builder->groupStart();
            $builder->like('items.name', $search);
            $builder->orLike('items.item_number', $search);
            $builder->orLike('inventory_outputs.comment', $search);
            $builder->groupEnd();
        }

        if (!empty($filters['reason'])) {
            $builder->where('inventory_outputs.reason', $filters['reason']);
        }

        if (!empty($filters['location_ids'])) {
            $builder->whereIn('inventory_outputs.location_id', $filters['location_ids']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $builder->where('DATE(inventory_outputs.created_at) BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
        }

        if ($count_only) {
            return $builder->get()->getRow()->count;
        }

        $builder->orderBy($sort, $order);

        if ($rows > 0) {
            $builder->limit($rows, $limit_from);
        }

        return $builder->get();
    }

    public function get_info(int $output_id): object
    {
        $builder = $this->db->table('inventory_outputs AS inventory_outputs');
        $builder->select('
            inventory_outputs.*,
            items.name AS item_name,
            items.item_number AS item_number,
            stock_locations.location_name AS location_name
        ');
        $builder->join('items AS items', 'items.item_id = inventory_outputs.item_id', 'LEFT');
        $builder->join('stock_locations AS stock_locations', 'stock_locations.location_id = inventory_outputs.location_id', 'LEFT');
        $builder->where('inventory_outputs.output_id', $output_id);

        $query = $builder->get();

        if ($query->getNumRows() == 1) {
            return $query->getRow();
        }

        $empty_obj = new stdClass();
        $empty_obj->output_id = -1;

        return $empty_obj;
    }

    public function soft_delete(int $output_id): bool
    {
        $builder = $this->db->table('inventory_outputs');
        $builder->where('output_id', $output_id);

        return $builder->update(['deleted' => 1]);
    }
}
