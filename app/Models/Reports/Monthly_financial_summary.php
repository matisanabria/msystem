<?php

namespace App\Models\Reports;

use Config\OSPOS;

class Monthly_financial_summary extends Report
{
    /**
     * @return array[]
     */
    protected function _get_data_columns(): array
    {
        return [
            ['month'           => lang('Reports.month')],
            ['ingresos'        => lang('Reports.total_ingresos'), 'sorter' => 'number_sorter'],
            ['costos'          => lang('Reports.total_costos'), 'sorter' => 'number_sorter'],
            ['resultado_bruto' => lang('Reports.resultado_bruto'), 'sorter' => 'number_sorter'],
            ['egresos'         => lang('Reports.total_egresos'), 'sorter' => 'number_sorter'],
            ['resultado_final' => lang('Reports.resultado_final'), 'sorter' => 'number_sorter'],
        ];
    }

    public function getDataColumns(): array
    {
        return $this->_get_data_columns();
    }

    /**
     * Returns month-by-month rows with ingresos, costos, resultado_bruto, egresos, resultado_final.
     *
     * @param array $inputs  Must contain 'start_date' and 'end_date'
     * @return array
     */
    public function getData(array $inputs): array
    {
        $config = config(OSPOS::class)->settings;
        $decimals = totals_decimals();

        $sales_by_month    = $this->_get_sales_by_month($config, $decimals, $inputs['start_date'], $inputs['end_date']);
        $expenses_by_month = $this->_get_expenses_by_month($config, $inputs['start_date'], $inputs['end_date']);

        $month_keys = array_unique(array_merge(array_keys($sales_by_month), array_keys($expenses_by_month)));
        sort($month_keys);

        $rows = [];
        foreach ($month_keys as $month_key) {
            $ingresos       = (float)($sales_by_month[$month_key]['ingresos'] ?? 0);
            $costos         = (float)($sales_by_month[$month_key]['costos'] ?? 0);
            $egresos        = (float)($expenses_by_month[$month_key]['egresos'] ?? 0);
            $resultado_bruto  = $ingresos - $costos;
            $resultado_final  = $resultado_bruto - $egresos;

            $rows[] = [
                'month_key'      => $month_key,
                'ingresos'       => $ingresos,
                'costos'         => $costos,
                'resultado_bruto' => $resultado_bruto,
                'egresos'        => $egresos,
                'resultado_final' => $resultado_final,
            ];
        }

        return $rows;
    }

    /**
     * Returns totals for the full date range.
     *
     * @param array $inputs
     * @return array
     */
    public function getSummaryData(array $inputs): array
    {
        $totals = [
            'ingresos'        => 0.0,
            'costos'          => 0.0,
            'resultado_bruto' => 0.0,
            'egresos'         => 0.0,
            'resultado_final' => 0.0,
        ];

        foreach ($this->getData($inputs) as $row) {
            $totals['ingresos']        += $row['ingresos'];
            $totals['costos']          += $row['costos'];
            $totals['resultado_bruto'] += $row['resultado_bruto'];
            $totals['egresos']         += $row['egresos'];
            $totals['resultado_final'] += $row['resultado_final'];
        }

        return $totals;
    }

    /**
     * Queries completed POS + Invoice sales grouped by month.
     * Returns keyed array: ['YYYY-MM' => ['ingresos' => ..., 'costos' => ...]]
     *
     * @param array  $config
     * @param int    $decimals
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    private function _get_sales_by_month(array $config, int $decimals, string $start_date, string $end_date): array
    {
        $sale_price = 'CASE WHEN sales_items.discount_type = ' . PERCENT
            . " THEN sales_items.quantity_purchased * sales_items.item_unit_price"
            . " - ROUND(sales_items.quantity_purchased * sales_items.item_unit_price * sales_items.discount / 100, $decimals)"
            . ' ELSE sales_items.quantity_purchased * (sales_items.item_unit_price - sales_items.discount) END';

        $sale_cost = 'sales_items.item_cost_price * sales_items.quantity_purchased';

        // Per-item tax using a correlated subquery (avoids dependency on temporary tables)
        $tax_subq = '(SELECT IFNULL(SUM(ROUND(sit.item_tax_amount, ' . $decimals . ')), 0)'
            . ' FROM ' . $this->db->prefixTable('sales_items_taxes') . ' AS sit'
            . ' WHERE sit.sale_id = sales_items.sale_id'
            . '   AND sit.item_id = sales_items.item_id'
            . '   AND sit.line = sales_items.line)';

        if ($config['tax_included']) {
            $ingresos_expr = "ROUND(SUM($sale_price), $decimals)";
        } else {
            $ingresos_expr = "ROUND(SUM($sale_price), $decimals) + SUM($tax_subq)";
        }

        if (empty($config['date_or_time_format'])) {
            $where_date = 'DATE(sales.sale_time) BETWEEN ' . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date);
        } else {
            $where_date = 'sales.sale_time BETWEEN ' . $this->db->escape(rawurldecode($start_date)) . ' AND ' . $this->db->escape(rawurldecode($end_date));
        }

        $sql = "SELECT
                    DATE_FORMAT(sales.sale_time, '%Y-%m') AS month_key,
                    $ingresos_expr AS ingresos,
                    SUM($sale_cost) AS costos
                FROM " . $this->db->prefixTable('sales_items') . " AS sales_items
                INNER JOIN " . $this->db->prefixTable('sales') . " AS sales
                    ON sales.sale_id = sales_items.sale_id
                WHERE $where_date
                  AND sales.sale_status = " . COMPLETED . "
                  AND (sales.sale_type = " . SALE_TYPE_POS . " OR sales.sale_type = " . SALE_TYPE_INVOICE . ")
                GROUP BY month_key
                ORDER BY month_key";

        $data = [];
        foreach ($this->db->query($sql)->getResultArray() as $row) {
            $data[$row['month_key']] = [
                'ingresos' => $row['ingresos'],
                'costos'   => $row['costos'],
            ];
        }

        return $data;
    }

    /**
     * Queries expenses grouped by month.
     * Returns keyed array: ['YYYY-MM' => ['egresos' => ...]]
     *
     * @param array  $config
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    private function _get_expenses_by_month(array $config, string $start_date, string $end_date): array
    {
        if (empty($config['date_or_time_format'])) {
            $where_date = 'DATE(expenses.date) BETWEEN ' . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date);
        } else {
            $where_date = 'expenses.date BETWEEN ' . $this->db->escape(rawurldecode($start_date)) . ' AND ' . $this->db->escape(rawurldecode($end_date));
        }

        $sql = "SELECT
                    DATE_FORMAT(expenses.date, '%Y-%m') AS month_key,
                    SUM(expenses.amount) AS egresos
                FROM " . $this->db->prefixTable('expenses') . " AS expenses
                WHERE $where_date
                  AND expenses.deleted = 0
                GROUP BY month_key
                ORDER BY month_key";

        $data = [];
        foreach ($this->db->query($sql)->getResultArray() as $row) {
            $data[$row['month_key']] = ['egresos' => $row['egresos']];
        }

        return $data;
    }
}
