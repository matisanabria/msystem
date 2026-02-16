<?php

namespace app\Libraries;

use App\Models\Enums\Rounding_mode;
use App\Libraries\Sale_lib;
use Config\OSPOS;

/**
 * Tax library
 *
 * Simplified for Paraguay IVA 10% (included in price, calculated as Price / 11)
 */
class Tax_lib
{
    public const TAX_TYPE_EXCLUDED = '1';
    public const TAX_TYPE_INCLUDED = '0';
    private Sale_lib $sale_lib;
    private array $config;

    public function __construct()
    {
        $this->sale_lib = new Sale_lib();
        $this->config = config(OSPOS::class)->settings;
    }

    /**
     * Compute IVA 10% for all items in the cart.
     * IVA is always included in the price: IVA = item_total / 11
     */
    public function get_taxes(array &$cart, int $sale_id = -1): array
    {
        $taxes = [];
        $item_taxes = [];
        $tax_name = $this->config['default_tax_1_name'] ?? 'IVA';
        $tax_rate = $this->config['default_tax_1_rate'] ?? '10';
        $tax_decimals = tax_decimals();

        $tax_group_index = 'X' . (float)$tax_rate . '-' . $tax_name;
        $total_tax_basis = '0.0';
        $total_tax_amount = '0.0';

        foreach ($cart as $line => $item) {
            $item_total = $this->sale_lib->get_item_total($item['quantity'], $item['price'], $item['discount'], $item['discount_type'], true);

            // IVA included: tax = item_total / 11 (for 10% IVA)
            $tax_fraction = bcdiv(bcadd('100', $tax_rate), '100');
            $price_tax_excl = bcdiv($item_total, $tax_fraction);
            $tax_amount = bcsub($item_total, $price_tax_excl);

            $total_tax_basis = bcadd($total_tax_basis, $item_total, 4);
            $total_tax_amount = bcadd($total_tax_amount, $tax_amount, 4);

            $cart[$line]['taxed_flag'] = lang('Sales.taxed_ind');

            $items_taxes_detail = [];
            $items_taxes_detail['item_id'] = $item['item_id'];
            $items_taxes_detail['line'] = $item['line'];
            $items_taxes_detail['name'] = $tax_name;
            $items_taxes_detail['percent'] = $tax_rate;
            $items_taxes_detail['tax_type'] = self::TAX_TYPE_INCLUDED;
            $items_taxes_detail['rounding_code'] = Rounding_mode::HALF_UP;
            $items_taxes_detail['cascade_sequence'] = 0;
            $items_taxes_detail['item_tax_amount'] = $tax_amount;
            $items_taxes_detail['sales_tax_code_id'] = null;
            $items_taxes_detail['jurisdiction_id'] = null;
            $items_taxes_detail['tax_category_id'] = null;

            $item_taxes[] = $items_taxes_detail;
        }

        // Round the total tax amount
        $rounded_tax_amount = round((float)$total_tax_amount, $tax_decimals, PHP_ROUND_HALF_UP);

        if ($total_tax_amount != 0) {
            $taxes[$tax_group_index] = [
                'sale_id'           => -1,
                'tax_type'          => self::TAX_TYPE_INCLUDED,
                'tax_group'         => $tax_name,
                'sale_tax_basis'    => $total_tax_basis,
                'sale_tax_amount'   => $rounded_tax_amount,
                'print_sequence'    => 1,
                'name'              => $tax_name,
                'tax_rate'          => $tax_rate,
                'sales_tax_code_id' => null,
                'jurisdiction_id'   => null,
                'tax_category_id'   => null,
                'rounding_code'     => Rounding_mode::HALF_UP
            ];
        }

        $tax_details = [];
        $tax_details[0] = $taxes;
        $tax_details[1] = $item_taxes;

        return $tax_details;
    }
}
