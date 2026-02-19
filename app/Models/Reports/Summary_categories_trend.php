<?php

namespace App\Models\Reports;

use Config\OSPOS;

class Summary_categories_trend extends Summary_report
{
    private int $top_n;

    /**
     * @return array[]
     */
    protected function _get_data_columns(): array
    {
        return [
            ['sale_date' => lang('Reports.date')],
            ['category'  => lang('Reports.category')],
            ['quantity'  => lang('Reports.quantity'), 'sorter' => 'number_sorter'],
            ['total'     => lang('Reports.total'), 'sorter' => 'number_sorter'],
        ];
    }

    /**
     * @param array $inputs
     * @param $builder
     * @return void
     */
    protected function _select(array $inputs, &$builder): void
    {
        parent::_select($inputs, $builder);

        $builder->select('
            DATE(sales.sale_time) AS sale_date,
            items.category AS category,
            SUM(sales_items.quantity_purchased) AS quantity_purchased
        ');
    }

    /**
     * @param $builder
     * @return void
     */
    protected function _from(&$builder): void
    {
        parent::_from($builder);

        $builder->join('items AS items', 'sales_items.item_id = items.item_id', 'inner');
    }

    /**
     * @param $builder
     * @return void
     */
    protected function _group_order(&$builder): void
    {
        $builder->groupBy('sale_date, category');
        $builder->orderBy('sale_date');
        $builder->orderBy('category');
    }

    /**
     * Get the top N categories by total sales for the given inputs.
     *
     * @param array $inputs
     * @param int $top_n
     * @return array
     */
    public function getTopCategories(array $inputs, int $top_n = 5): array
    {
        $config = config(OSPOS::class)->settings;

        $builder = $this->db->table('sales_items AS sales_items');

        $builder->select('items.category AS category, SUM(sales_items.quantity_purchased) AS total_qty');
        $builder->join('sales AS sales', 'sales_items.sale_id = sales.sale_id', 'inner');
        $builder->join('items AS items', 'sales_items.item_id = items.item_id', 'inner');

        if (empty($config['date_or_time_format'])) {
            $builder->where('DATE(sales.sale_time) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));
        } else {
            $builder->where('sales.sale_time BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date'])));
        }

        if ($inputs['location_id'] != 'all') {
            $builder->where('sales_items.item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'complete') {
            $builder->where('sales.sale_status', COMPLETED);
            $builder->groupStart();
            $builder->where('sales.sale_type', SALE_TYPE_POS);
            $builder->orWhere('sales.sale_type', SALE_TYPE_INVOICE);
            $builder->orWhere('sales.sale_type', SALE_TYPE_RETURN);
            $builder->groupEnd();
        } elseif ($inputs['sale_type'] == 'sales') {
            $builder->where('sales.sale_status', COMPLETED);
            $builder->groupStart();
            $builder->where('sales.sale_type', SALE_TYPE_POS);
            $builder->orWhere('sales.sale_type', SALE_TYPE_INVOICE);
            $builder->groupEnd();
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('sales.sale_status', COMPLETED);
            $builder->where('sales.sale_type', SALE_TYPE_RETURN);
        }

        $builder->where('items.category IS NOT NULL');
        $builder->where("items.category != ''");
        $builder->groupBy('category');
        $builder->orderBy('total_qty', 'DESC');
        $builder->limit($top_n);

        $results = $builder->get()->getResultArray();

        return array_column($results, 'category');
    }
}
