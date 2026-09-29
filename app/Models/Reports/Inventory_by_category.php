<?php

namespace App\Models\Reports;

/**
 *
 *
 */
class Inventory_by_category extends Report
{
    /**
     * @return array[]
     */
    public function getDataColumns(): array
    {
        return [
            ['category'         => lang('Reports.category')],
            ['item_count'       => lang('Reports.category_item_count')],
            ['total_unit_price' => lang('Reports.unit_price'), 'sorter' => 'number_sorter'],
            ['total_wholesale'  => lang('Items.price_wholesale'), 'sorter' => 'number_sorter'],
            ['total_reseller'   => lang('Items.price_reseller'), 'sorter' => 'number_sorter']
        ];
    }

    /**
     * @param array $inputs
     * @return array
     */
    public function getData(array $inputs): array
    {
        $builder = $this->db->table('items AS items');
        $builder->select(
            "COALESCE(NULLIF(items.category, ''), '" . $this->db->escapeString(lang('Reports.no_category')) . "') AS category,
            COUNT(items.item_id) AS item_count,
            SUM(items.unit_price * item_quantities.quantity) AS total_unit_price,
            SUM(items.price_wholesale * item_quantities.quantity) AS total_wholesale,
            SUM(items.price_reseller * item_quantities.quantity) AS total_reseller"
        );
        $builder->join('item_quantities AS item_quantities', 'items.item_id = item_quantities.item_id');
        $builder->join('stock_locations AS stock_locations', 'item_quantities.location_id = stock_locations.location_id');
        $builder->where('items.deleted', 0);
        $builder->where('items.stock_type', 0);
        $builder->where('stock_locations.deleted', 0);

        // Should be corresponding to the values Inventory_summary::getItemCountDropdownArray() returns
        if ($inputs['item_count'] == 'zero_and_less') {
            $builder->where('item_quantities.quantity <=', 0);
        } elseif ($inputs['item_count'] == 'more_than_zero') {
            $builder->where('item_quantities.quantity >', 0);
        }

        $builder->where('stock_locations.location_id', $inputs['location_id']);

        $builder->groupBy('category');
        $builder->orderBy('category');

        return $builder->get()->getResultArray();
    }

    /**
     * @param array $inputs expects the reports-data-array which Inventory_by_category::getData() returns
     * @return array
     */
    public function getSummaryData(array $inputs): array
    {
        $return = [
            'total_items'      => 0,
            'total_unit_price' => 0,
            'total_wholesale'  => 0,
            'total_reseller'   => 0
        ];

        foreach ($inputs as $input) {
            $return['total_items'] += $input['item_count'];
            $return['total_unit_price'] += $input['total_unit_price'];
            $return['total_wholesale'] += $input['total_wholesale'];
            $return['total_reseller'] += $input['total_reseller'];
        }

        return $return;
    }
}
