<?php
/**
 * @var string $controller_name
 * @var array $modes
 * @var array $mode
 * @var array $empty_tables
 * @var array $selected_table
 * @var array $stock_locations
 * @var array $stock_location
 * @var array $cart
 * @var bool $items_module_allowed
 * @var bool $change_price
 * @var int $customer_id
 * @var int $customer_discount_type
 * @var float $customer_discount
 * @var float $customer_total
 * @var string $customer_required
 * @var float|int $item_count
 * @var float|int $total_units
 * @var float $subtotal
 * @var array $taxes
 * @var float $total
 * @var float $payments_total
 * @var float $amount_due
 * @var bool $payments_cover_total
 * @var array $payment_options
 * @var array $selected_payment_type
 * @var bool $pos_mode
 * @var array $payments
 * @var string $mode_label
 * @var string $comment
 * @var bool $print_after_sale
 * @var bool $email_receipt
 * @var bool $price_work_orders
 * @var string $invoice_number
 * @var int $cash_mode
 * @var float $non_cash_total
 * @var float $cash_amount_due
 * @var array $config
 * @var int $current_cashier_id
 * @var string $current_cashier_name
 */

use App\Models\Employee;

?>

<?= view('partial/header') ?>

<?php
if (isset($error)) {
    echo '<div class="alert alert-dismissible alert-danger" role="alert">' . esc($error) . '</div>';
}

// Insufficient stock is shown inside the affected cart line (computed from the cart, so it stays until fixed)
$requested_by_stock = [];
foreach ($cart as $cart_item) {
    $stock_key = $cart_item['item_id'] . '|' . $cart_item['item_location'];
    $requested_by_stock[$stock_key] = ($requested_by_stock[$stock_key] ?? 0) + (float) $cart_item['quantity'];
}
$stock_warnings = [];
foreach ($cart as $cart_line => $cart_item) {
    if ($cart_item['item_type'] != ITEM_TEMP && $cart_item['stock_type'] == '0') {
        $requested = $requested_by_stock[$cart_item['item_id'] . '|' . $cart_item['item_location']];
        if ((float) $cart_item['in_stock'] - $requested < 0) {
            $stock_warnings[$cart_line] = lang('Sales.stock_insufficient', [
                $cart_item['name'],
                to_quantity_decimals($requested),
                to_quantity_decimals($cart_item['in_stock']),
                $cart_item['stock_name']
            ]);
        }
    }
}

if (!empty($warning) && !($warning === lang('Sales.quantity_less_than_zero') && !empty($stock_warnings))) {
    echo '<div class="alert alert-dismissible alert-warning" role="alert">' . esc($warning) . '</div>';
}

if (isset($success)) {
    echo '<div class="alert alert-dismissible alert-success" role="status">' . esc($success) . '</div>';
}

helper('url');
?>

<div id="register_wrapper" class="sales-screen">

    <!-- Top register controls -->
    <?= form_open("$controller_name/changeMode", ['id' => 'mode_form', 'class' => 'form-horizontal register-section']) ?>
        <div class="card-body form-group">
            <ul>
                <li class="float-start first_li">
                    <label class="control-label" for="mode"><?= lang(ucfirst($controller_name) . '.mode') ?></label>
                </li>
                <li class="float-start">
                    <?= form_dropdown('mode', $modes, $mode, ['id' => 'mode', 'onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary btn-sm', 'data-width' => 'fit']) ?>
                </li>
                <?php if ($config['dinner_table_enable']) { ?>
                    <li class="float-start first_li">
                        <label class="control-label"><?= lang(ucfirst($controller_name) . '.table') ?></label>
                    </li>
                    <li class="float-start">
                        <?= form_dropdown('dinner_table', $empty_tables, $selected_table, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary btn-sm', 'data-width' => 'fit']) ?>
                    </li>
                <?php } ?>
                <?php if (count($stock_locations) > 1) { ?>
                    <li class="float-start">
                        <label class="control-label" for="stock_location"><?= lang(ucfirst($controller_name) . '.stock_location') ?></label>
                    </li>
                    <li class="float-start">
                        <?= form_dropdown('stock_location', $stock_locations, $stock_location, ['id' => 'stock_location', 'onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary btn-sm', 'data-width' => 'fit']) ?>
                    </li>
                <?php } ?>

                <li class="float-end">
                    <?php $suspended_count = (int) ($suspended_count ?? 0); ?>
                    <button type="button" class="btn btn-neutral btn-sm" id="show_suspended_sales_button" data-href="<?= esc(site_url("$controller_name/suspended")) ?>"
                        title="<?= lang(ucfirst($controller_name) . '.suspended_sales') ?>">
                        <span class="bi bi-justify" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.suspended_sales') ?><?= $suspended_count > 0 ? " ($suspended_count)" : '' ?>
                    </button>
                </li>

                <li class="float-end">
                    <a href="<?= esc(site_url("$controller_name/stockConsult")) ?>" class="btn btn-neutral btn-sm" id="stock_consult_button"
                        title="<?= lang('Sales.stock_consult') ?>">
                        <span class="bi bi-list-ul" aria-hidden="true"></span> <?= lang('Sales.stock_consult') ?>
                    </a>
                </li>

                <?php
                $employee = model(Employee::class);
                if ($employee->has_grant('reports_sales', session('person_id'))) {
                ?>
                    <li class="float-end">
                        <?= anchor(
                            "$controller_name/manage",
                            '<span class="bi bi-card-list" aria-hidden="true"></span> ' . lang(ucfirst($controller_name) . '.takings'),
                            array('class' => 'btn btn-neutral btn-sm', 'id' => 'sales_takings_button', 'title' => lang(ucfirst($controller_name) . '.takings'))
                        ) ?>
                    </li>
                <?php } ?>

                <li class="float-end cashier-item">
                    <?php if ($current_cashier_id > 0): ?>
                        <span id="current_cashier_badge" class="cashier-label">
                            <span class="bi bi-person" aria-hidden="true"></span> <?= lang('Sales.cashier') ?>: <?= esc($current_cashier_name) ?>
                        </span>
                    <?php else: ?>
                        <span id="current_cashier_badge" class="cashier-label">
                            <span class="bi bi-person" aria-hidden="true"></span> <?= lang('Sales.no_cashier') ?>
                        </span>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    <?= form_close() ?>

        <?= form_open("$controller_name/add", ['id' => 'add_item_form', 'class' => 'form-horizontal register-section']) ?>
        <div class="card-body form-group">
            <ul>
                <li class="float-start first_li">
                    <label for="item" class="control-label"><?= lang(ucfirst($controller_name) . '.find_or_scan_item_or_receipt') ?></label>
                </li>
                <li class="float-start">
                    <?= form_input(['name' => 'item', 'id' => 'item', 'class' => 'form-control form-control-sm', 'size' => '50', 'autocomplete' => 'off', 'placeholder' => lang(ucfirst($controller_name) . '.start_typing_item_name')]) ?>
                    <span class="ui-helper-hidden-accessible" role="status"></span>
                </li>
                <li class="float-end">
                    <button type="button" id="new_item_button" class="btn btn-info btn-sm float-end modal-dlg" data-btn-new="<?= lang('Common.new') ?>" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "items/view" ?>" title="<?= lang(ucfirst($controller_name) . ".new_item") ?>">
                        <span class="bi bi-tag" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . ".new_item") ?>
                    </button>
                </li>
            </ul>
        </div>
    <?= form_close() ?>


    <!-- Sale Items List -->

    <?= view('partial/photo_modal') ?>
    <table class="sales_table_100" id="register">
        <thead>
            <tr>
                <th style="width: 5%;"><?= lang('Common.delete') ?></th>
                <th style="width: 15%;"><?= lang(ucfirst($controller_name) . '.item_number') ?></th>
                <th style="width: 24%;"><?= lang(ucfirst($controller_name) . '.item_name') ?></th>
                <th style="width: 16%;"><?= lang(ucfirst($controller_name) . '.price') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.quantity') ?></th>
                <th style="width: 15%;"><?= lang(ucfirst($controller_name) . '.discount') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.total') ?></th>
                <th style="width: 5%;"><?= lang(ucfirst($controller_name) . '.update') ?></th>
            </tr>
        </thead>

        <tbody id="cart_contents">
            <?php if (count($cart) == 0) { ?>
                <tr>
                    <td colspan="8">
                        <div class="cart-empty"><span class="bi bi-cart" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.no_items_in_cart') ?></div>
                    </td>
                </tr>
            <?php
            } else {
                foreach (array_reverse($cart, true) as $line => $item) {
            ?>
                    <?= form_open("$controller_name/editItem/$line", ['class' => 'form-horizontal', 'id' => "cart_$line"]) ?>
                        <?php
                        $item_pic_src = null;
                        if ($item['item_type'] != ITEM_TEMP && !empty($item['pic_filename'])) {
                            $ext = pathinfo($item['pic_filename'], PATHINFO_EXTENSION);
                            $images = $ext == ''
                                ? glob("./uploads/item_pics/{$item['pic_filename']}.*")
                                : glob("./uploads/item_pics/{$item['pic_filename']}");
                            $item_pic_src = sizeof($images) > 0 ? base_url($images[0]) : null;
                        }
                        // The description line only exists when there is something to show or edit
                        $has_second_row = $item['item_type'] == ITEM_TEMP || $item['allow_alt_description'] || $item['description'] != '';
                        $line_classes = trim((isset($stock_warnings[$line]) ? 'cart-line-warn ' : '') . ($has_second_row ? '' : 'cart-line-solo'));
                        ?>
                        <tr<?= $line_classes !== '' ? ' class="' . $line_classes . '"' : '' ?>>
                            <td>
                                <?php
                                echo anchor("$controller_name/deleteItem/$line", '<span class="bi bi-trash" aria-hidden="true"></span>', ['class' => 'cart-icon-btn cart-icon-btn-danger', 'aria-label' => lang('Sales.register_delete_label', [$item['name']]), 'title' => lang('Common.delete')]);
                                if (!$has_second_row) {
                                    // No description line: the same hidden fields still travel with the form
                                    echo form_hidden('description', '') . form_hidden('serialnumber', '');
                                }
                                echo form_hidden('location', (string)$item['item_location']);
                                echo form_input(['type' => 'hidden', 'name' => 'item_id', 'value' => $item['item_id']]);
                                ?>
                            </td>
                            <?php if ($item['item_type'] == ITEM_TEMP) { ?>
                                <td><?= form_input(['name' => 'item_number', 'id' => 'item_number', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.item_number'), 'value' => $item['item_number']]) ?></td>
                                <td style="align: center;">
                                    <?= form_input(['name' => 'name', 'id' => 'name', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.item_name'), 'value' => $item['name']]) ?>
                                </td>
                            <?php } else { ?>
                                <td><?= esc($item['item_number']) ?></td>
                                <td style="align: center;">
                                    <div class="cart-item-cell">
                                        <?php if ($item_pic_src): ?>
                                            <button type="button" class="item-photo-btn item-photo-btn-lg" data-photo-src="<?= esc($item_pic_src, 'attr') ?>" data-photo-name="<?= esc($item['name'], 'attr') ?>" aria-label="<?= esc(lang('Items.view_photo', [$item['name']]), 'attr') ?>">
                                                <img alt="" src="<?= esc($item_pic_src, 'attr') ?>" loading="lazy">
                                            </button>
                                        <?php endif; ?>
                                        <div class="cart-item-text">
                                            <?= esc($item['name']) . ' ' . implode(' ', [$item['attribute_values'], $item['attribute_dtvalues']]) ?>
                                            <?php if ($item['stock_type'] == '0'): ?>
                                                <?php if ((float) $item['in_stock'] <= 0): ?>
                                                    <div class="cart-stock cart-stock-out"><span class="bi bi-exclamation-circle" aria-hidden="true"></span> <?= lang('Sales.no_stock') ?></div>
                                                <?php else: ?>
                                                    <div class="cart-stock"><?= lang('Sales.stock_label', [to_quantity_decimals($item['in_stock']), esc($item['stock_name'])]) ?></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if (isset($stock_warnings[$line])): ?>
                                                <div class="cart-stock-warning" role="status" aria-live="polite"><span class="bi bi-exclamation-triangle-fill" aria-hidden="true"></span> <?= esc($stock_warnings[$line]) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            <?php } ?>

                            <td>
                                <?php
                                if ($items_module_allowed && $change_price) {
                                    echo form_input(['name' => 'price', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.register_price_label', [$item['name']]), 'value' => to_currency_no_money($item['price']), 'onClick' => 'this.select();']);
                                } else {
                                    echo to_currency($item['price']);
                                    echo form_hidden('price', to_currency_no_money($item['price']));
                                }
                                $current_price_type = (int)($item['price_type'] ?? 0);
                                if ($items_module_allowed && $change_price) {
                                    $price_types = [
                                        0 => ['label' => lang('Sales.price_type_sale'), 'short' => lang('Sales.price_type_sale_short')],
                                        1 => ['label' => lang('Sales.price_type_wholesale'), 'short' => lang('Sales.price_type_wholesale_short')],
                                        2 => ['label' => lang('Sales.price_type_reseller'), 'short' => lang('Sales.price_type_reseller_short')],
                                    ];
                                    echo form_dropdown(
                                        'price_type',
                                        array_map(static fn ($type) => $type['label'], $price_types),
                                        $current_price_type,
                                        [
                                            'class'          => 'price_type_select d-none',
                                            'data-original'  => (string)$current_price_type,
                                            'data-wholesale' => to_currency_no_money($item['price_wholesale'] ?? $item['price']),
                                            'data-reseller'  => to_currency_no_money($item['price_reseller'] ?? $item['price']),
                                        ]
                                    );
                                    ?>
                                    <div class="segmented price-type" role="group" aria-label="<?= esc(lang('Sales.price_type_sale')) ?>">
                                        <?php foreach ($price_types as $type_value => $type) { ?>
                                            <button type="button" class="segmented-option<?= $current_price_type === $type_value ? ' active' : '' ?>" data-value="<?= $type_value ?>" title="<?= esc($type['label']) ?>" aria-pressed="<?= $current_price_type === $type_value ? 'true' : 'false' ?>"><?= esc($type['short']) ?></button>
                                        <?php } ?>
                                    </div>
                                    <?php
                                } else {
                                    echo form_hidden('price_type', (string)$current_price_type);
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                if ($item['is_serialized']) {
                                    echo to_quantity_decimals($item['quantity']);
                                    echo form_hidden('quantity', $item['quantity']);
                                } else {
                                    echo form_input(['name' => 'quantity', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.register_quantity_label', [$item['name']]), 'value' => to_quantity_decimals($item['quantity']), 'onClick' => 'this.select();']);
                                }
                                ?>
                            </td>

                            <td>
                                <div class="input-group input-group-sm">
                                    <?= form_input(['name' => 'discount', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.register_discount_label', [$item['name']]), 'value' => $item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'onClick' => 'this.select();', 'data-original' => $item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'data-original-type' => (string)(int)$item['discount_type']]) ?>
                                    <?= form_checkbox(['id' => "discount_toggle_$line", 'name' => 'discount_toggle', 'value' => 1, 'class' => 'd-none', 'data-line' => $line, 'checked' => $item['discount_type'] == 1]) ?>
                                    <div class="segmented discount-type" role="group" aria-label="<?= esc(lang(ucfirst($controller_name) . '.discount')) ?>">
                                        <button type="button" class="segmented-option<?= $item['discount_type'] == 1 ? '' : ' active' ?>" data-value="0" aria-pressed="<?= $item['discount_type'] == 1 ? 'false' : 'true' ?>">%</button>
                                        <button type="button" class="segmented-option<?= $item['discount_type'] == 1 ? ' active' : '' ?>" data-value="1" aria-pressed="<?= $item['discount_type'] == 1 ? 'true' : 'false' ?>"><?= esc($config['currency_symbol']) ?></button>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <?php
                                if ($item['item_type'] == ITEM_AMOUNT_ENTRY) {    // TODO: === ?
                                    echo form_input(['name' => 'discounted_total', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.register_total_label', [$item['name']]), 'value' => to_currency_no_money($item['discounted_total']), 'onClick' => 'this.select();']);
                                } else {
                                    echo to_currency($item['discounted_total']);
                                }
                                ?>
                            </td>

                            <td>
                                <a href="javascript:$('#<?= "cart_$line" ?>').submit();" class="cart-icon-btn" title="<?= lang(ucfirst($controller_name) . '.update') ?>" aria-label="<?= esc(lang('Sales.register_update_label', [$item['name']])) ?>">
                                    <span class="bi bi-arrow-clockwise" aria-hidden="true"></span>
                                </a>
                            </td>
                        </tr>
                        <?php if ($has_second_row): ?>
                        <tr<?= isset($stock_warnings[$line]) ? ' class="cart-line-warn"' : '' ?>>
                            <?php if ($item['item_type'] == ITEM_TEMP) { ?>
                                <td><?= form_input(['type' => 'hidden', 'name' => 'item_id', 'value' => $item['item_id']]) ?></td>
                                <td style="align: center;" colspan="6">
                                    <?= form_input(['name' => 'item_description', 'id' => 'item_description', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.register_description_label', [$item['name']]), 'value' => $item['description']]) ?>
                                </td>
                                <td> </td>
                            <?php } else { ?>
                                <td>&nbsp;</td>
                                <?php if ($item['allow_alt_description']) { ?>
                                    <td style="color: #2F4F4F;"><?= lang(ucfirst($controller_name) . '.description_abbrv') ?></td>
                                <?php } ?>

                                <td colspan="2" style="text-align: left;">
                                    <?php
                                    if ($item['allow_alt_description']) {
                                        echo form_input(['name' => 'description', 'class' => 'form-control form-control-sm', 'aria-label' => lang('Sales.register_description_label', [$item['name']]), 'value' => $item['description'], 'onClick' => 'this.select();']);
                                    } else {
                                        echo esc($item['description']);
                                        echo form_hidden('description', $item['description']);
                                    }
                                    ?>
                                </td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td colspan="4" style="text-align: left;">
                                    <?= form_hidden('serialnumber', '') ?>
                                </td>
                            <?php } ?>
                        </tr>
                        <?php endif; ?>
                    <?= form_close() ?>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Overall Sale -->

<div id="overall_sale" class="card sales-screen">
    <div class="card-body">
        <?= form_open("$controller_name/selectCustomer", ['id' => 'select_customer_form', 'class' => 'form-horizontal']) ?>
            <?php if (isset($customer)) { ?>
                <table class="sales_table_100">
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer') ?></th>
                        <th style="width: 45%; text-align: right;"><?= anchor("customers/view/$customer_id", $customer, ['class' => 'modal-dlg', 'data-btn-submit' => lang('Common.submit'), 'title' => lang('Customers.update')]) ?></th>
                    </tr>
                    <?php if (!empty($customer_email)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_email') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($customer_email) ?></th>
                        </tr>
                    <?php } ?>
                    <?php if (!empty($customer_address)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_address') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($customer_address) ?></th>
                        </tr>
                    <?php } ?>
                    <?php if (!empty($customer_location)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_location') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($customer_location) ?></th>
                        </tr>
                    <?php } ?>
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_discount') ?></th>
                        <th style="width: 45%; text-align: right;"><?= ($customer_discount_type == FIXED) ? to_currency($customer_discount) : $customer_discount . '%' ?></th>
                    </tr>
                    <?php if ($config['customer_reward_enable']): ?>
                        <?php if (!empty($customer_rewards)) { ?>
                            <tr>
                                <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.rewards_package') ?></th>
                                <th style="width: 45%; text-align: right;"><?= esc($customer_rewards['package_name']) ?></th>
                            </tr>
                            <tr>
                                <th style="width: 55%;"><?= lang('Customers.available_points') ?></th>
                                <th style="width: 45%; text-align: right;"><?= esc($customer_rewards['points']) ?></th>
                            </tr>
                        <?php } ?>
                    <?php endif; ?>
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_total') ?></th>
                        <th style="width: 45%; text-align: right;"><?= to_currency($customer_total) ?></th>
                    </tr>
                    <?php if (!empty($mailchimp_info)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_mailchimp_status') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($mailchimp_info['status']) ?></th>
                        </tr>
                    <?php } ?>
                </table>

                <?= anchor(
                    "$controller_name/removeCustomer",
                    '<span class="bi bi-x-lg" aria-hidden="true"></span> ' . lang('Common.remove') . ' ' . lang('Customers.customer'),
                    ['class' => 'btn btn-danger btn-sm', 'id' => 'remove_customer_button', 'title' => lang('Common.remove') . ' ' . lang('Customers.customer')]
                )
                ?>
            <?php } else { ?>
                <div class="form-group" id="select_customer">
                    <label id="customer_label" for="customer" class="control-label" style="margin-bottom: 1em; margin-top: -1em;">
                        <?= lang(ucfirst($controller_name) . '.select_customer') . esc(" $customer_required") ?>
                    </label>
                    <?= form_input(['name' => 'customer', 'id' => 'customer', 'class' => 'form-control form-control-sm', 'autocomplete' => 'off', 'placeholder' => lang(ucfirst($controller_name) . '.start_typing_customer_name')]) ?>

                    <button type="button" class="btn btn-info btn-sm modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "customers/view" ?>" title="<?= lang(ucfirst($controller_name) . ".new_customer") ?>">
                        <span class="bi bi-person" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . ".new_customer") ?>
                    </button>
                    <button type="button" class="btn btn-neutral btn-sm modal-dlg" id="show_keyboard_help" data-href="<?= esc("$controller_name/salesKeyboardHelp") ?>" title="<?= lang(ucfirst($controller_name) . '.key_title') ?>">
                        <span class="bi bi-keyboard" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.key_help') ?>
                    </button>
                </div>
            <?php } ?>
        <?= form_close() ?>

        <?php
        $has_cart = count($cart) > 0;
        $channels = [
            'store'    => lang('Sales.sale_channel_store'),
            'delivery' => lang('Sales.sale_channel_delivery'),
            'shipping' => lang('Sales.sale_channel_shipping'),
        ];
        ?>

        <?php if ($has_cart) { // The channel applies to the whole sale, so it is asked as soon as there is something to sell ?>
            <fieldset class="channel-field" id="sale_channel_field">
                <legend class="channel-legend"><?= lang('Sales.sale_channel') ?></legend>
                <div class="segmented segmented-block segmented-radio">
                    <?php foreach ($channels as $channel_value => $channel_label) { ?>
                        <label class="segmented-option">
                            <input type="radio" name="sale_channel" value="<?= $channel_value ?>" form="buttons_form"<?= $channel_value === 'store' ? ' checked' : '' ?>>
                            <span><?= $channel_label ?></span>
                        </label>
                    <?php } ?>
                </div>
            </fieldset>
        <?php } ?>

        <table class="sales_table_100" id="sale_totals">
            <tr>
                <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.quantity_of_items', [$item_count]) ?></th>
                <th style="width: 45%; text-align: right;"><?= $total_units ?></th>
            </tr>
            <?php foreach ($taxes as $tax_group_index => $tax) { ?>
                <tr>
                    <th style="width: 55%;"><?= (float)$tax['tax_rate'] . '% ' . $tax['tax_group'] ?></th>
                    <th style="width: 45%; text-align: right;"><?= to_currency_tax($tax['sale_tax_amount']) ?></th>
                </tr>
            <?php } ?>
            <tr>
                <th style="width: 55%; font-size: 150%"><?= lang(ucfirst($controller_name) . '.total') ?></th>
                <th style="width: 45%; font-size: 150%; text-align: right;"><span id="sale_total" class="sale-total"><?= to_currency($total) ?></span></th>
            </tr>
        </table>

        <?php if ($has_cart) { // Only show this part if there are Items already in the register ?>
            <?php
            // The Complete button needs the payments to cover the total (in sale/return mode), and a customer when part is on credit
            $show_finish = false;
            if ($payments_cover_total && $pos_mode) {
                $due_payment = false;
                foreach ($payments as $payment) {
                    if ($payment['payment_type'] == lang(ucfirst($controller_name) . '.due')) {
                        $due_payment = true;
                    }
                }
                $show_finish = !$due_payment || isset($customer);
            }
            ?>

            <table class="sales_table_100" id="payment_totals">
                <tr>
                    <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.payments_total') ?></th>
                    <th style="width: 45%; text-align: right;"><?= to_currency($payments_total) ?></th>
                </tr>
                <tr>
                    <th style="width: 55%; font-size: 120%"><?= lang('Sales.money_remaining') ?></th>
                    <th style="width: 45%; font-size: 120%; text-align: right;"><span id="sale_amount_due"><?= to_currency($amount_due) ?></span></th>
                </tr>
            </table>

            <div id="payment_details">
                <?php if ($payments_cover_total) { // Nothing left to pay: the payment fields stay for reference, disabled ?>
                    <?= form_open("$controller_name/addPayment", ['id' => 'add_payment_form', 'class' => 'form-horizontal d-none']) ?>
                        <table class="sales_table_100 payment-form">
                            <tr>
                                <td><label for="payment_types"><?= lang(ucfirst($controller_name) . '.payment') ?></label></td>
                                <td>
                                    <?= form_dropdown('payment_type', $payment_options, $selected_payment_type, ['id' => 'payment_types', 'aria-label' => lang(ucfirst($controller_name) . '.payment'), 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary btn-sm', 'data-width' => 'fit', 'disabled' => 'disabled']) ?>
                                </td>
                            </tr>
                            <tr>
                                <td><label id="amount_tendered_label" for="amount_tendered"><?= lang(ucfirst($controller_name) . '.amount_tendered') ?></label></td>
                                <td>
                                    <?= form_input(['name' => 'amount_tendered', 'id' => 'amount_tendered', 'class' => 'form-control form-control-sm disabled', 'disabled' => 'disabled', 'value' => '0', 'size' => '5', 'onClick' => 'this.select();']) ?>
                                </td>
                            </tr>
                        </table>
                    <?= form_close() ?>
                <?php } else { ?>
                    <?= form_open("$controller_name/addPayment", ['id' => 'add_payment_form', 'class' => 'form-horizontal']) ?>
                        <table class="sales_table_100 payment-form">
                            <tr>
                                <td><label for="payment_types"><?= lang(ucfirst($controller_name) . '.payment') ?></label></td>
                                <td>
                                    <?= form_dropdown('payment_type', $payment_options,  $selected_payment_type, ['id' => 'payment_types', 'aria-label' => lang(ucfirst($controller_name) . '.payment'), 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary btn-sm', 'data-width' => 'fit']) ?>
                                </td>
                            </tr>
                            <tr>
                                <td><label id="amount_tendered_label" for="amount_tendered"><?= lang(ucfirst($controller_name) . '.amount_tendered') ?></label></td>
                                <td>
                                    <?= form_input(['name' => 'amount_tendered', 'id' => 'amount_tendered', 'class' => 'form-control form-control-sm non-giftcard-input', 'value' => to_currency_no_money($amount_due), 'size' => '5', 'onClick' => 'this.select();']) ?>
                                    <?= form_input(['name' => 'amount_tendered', 'id' => 'giftcard_number', 'class' => 'form-control form-control-sm giftcard-input', 'disabled' => true, 'value' => to_currency_no_money($amount_due), 'size' => '5']) ?>
                                </td>
                            </tr>
                        </table>
                    <?= form_close() ?>

                    <div class="payment-actions">
                        <button type="button" class="btn btn-sm btn-success" id="add_payment_button">
                            <span class="bi bi-credit-card" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.add_payment') ?>
                        </button>
                    </div>
                <?php } ?>

                <?php if (count($payments) > 0) { // Only show this part if there is at least one payment entered. ?>
                    <table class="sales_table_100 payments-table" id="payments_table">
                        <thead>
                            <tr>
                                <th><?= lang(ucfirst($controller_name) . '.payment_type') ?></th>
                                <th class="payment-amount"><?= lang(ucfirst($controller_name) . '.payment_amount') ?></th>
                                <th class="payment-remove-cell"><span class="visually-hidden"><?= lang('Sales.remove_payment') ?></span></th>
                            </tr>
                        </thead>

                        <tbody id="payment_contents">
                            <?php foreach ($payments as $payment_id => $payment) { ?>
                                <tr>
                                    <td><?= $payment['payment_type'] ?></td>
                                    <td class="payment-amount"><?= to_currency($payment['payment_amount']) ?></td>
                                    <td class="payment-remove-cell">
                                        <?= anchor(
                                            "$controller_name/deletePayment/" . base64url_encode($payment_id),
                                            '<span class="bi bi-trash3-fill" aria-hidden="true"></span><span>' . lang('Sales.remove_payment') . '</span>',
                                            ['class' => 'payment-remove', 'aria-label' => lang('Sales.remove_payment') . ': ' . strip_tags($payment['payment_type']), 'title' => lang('Sales.remove_payment')]
                                        ) ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>

            <div class="comment-field" id="comment_field">
                <button type="button" class="btn btn-link comment-toggle" id="comment_toggle" aria-expanded="<?= $comment !== '' && $comment !== null ? 'true' : 'false' ?>" aria-controls="comment_box"<?= $comment !== '' && $comment !== null ? ' hidden' : '' ?>>
                    <?= lang('Sales.add_comment') ?>
                </button>
                <div id="comment_box"<?= $comment === '' || $comment === null ? ' hidden' : '' ?>>
                    <?= form_label(lang('Common.comments'), 'comment', ['class' => 'control-label', 'id' => 'comment_label']) ?>
                    <?= form_textarea(['name' => 'comment', 'id' => 'comment', 'class' => 'form-control form-control-sm', 'value' => $comment, 'rows' => '2']) ?>
                </div>
            </div>

            <div class="print-field">
                <input type="checkbox" name="sales_print_after_sale" id="sales_print_after_sale" value="1"<?= $print_after_sale ? ' checked' : '' ?>>
                <label for="sales_print_after_sale"><?= lang(ucfirst($controller_name) . '.print_after_sale') ?></label>
            </div>

            <?php if ($mode == 'sale_work_order') { ?>
                <div class="print-field">
                    <input type="checkbox" name="price_work_orders" id="price_work_orders" value="1"<?= $price_work_orders ? ' checked' : '' ?>>
                    <label for="price_work_orders"><?= lang(ucfirst($controller_name) . '.include_prices') ?></label>
                </div>
            <?php } ?>

            <?php if (($mode == 'sale_invoice') && $config['invoice_enable']) { ?>
                <div class="invoice-field">
                    <label for="sales_invoice_number"><?= lang(ucfirst($controller_name) . '.invoice_enable') ?></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text form-control-sm">#</span>
                        <?= form_input(['name' => 'sales_invoice_number', 'id' => 'sales_invoice_number', 'class' => 'form-control form-control-sm', 'value' => $invoice_number]) ?>
                    </div>
                </div>
            <?php } ?>

            <?php if ($show_finish) { ?>
                <button type="button" class="btn btn-success panel-primary" id="finish_sale_button">
                    <span class="bi bi-check-lg" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.complete_sale') ?>
                </button>
            <?php } ?>

            <?= form_open("$controller_name/cancel", ['id' => 'buttons_form']) ?>
                <?php if (!$pos_mode && isset($customer)) { // Invoice / quote without payment ?>
                    <button type="button" class="btn btn-success panel-primary" id="finish_invoice_quote_button"><span class="bi bi-check-lg" aria-hidden="true"></span> <?= esc($mode_label) ?></button>
                <?php } ?>

                <div class="panel-actions">
                    <button type="button" class="btn btn-sm btn-neutral" id="suspend_sale_button"><span class="bi bi-justify" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.suspend_sale') ?></button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="cancel_sale_button"><span class="bi bi-x-lg" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.cancel_sale') ?></button>
                </div>
            <?= form_close() ?>
        <?php } ?>
    </div>
</div>

<!-- Discount Authorization Modal -->
<div class="modal fade" id="da_modal" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <!-- State 1: Request authorization -->
            <div id="da_state_request">
                <div class="modal-header" style="background:#d9534f; color:#fff; border-radius:3px 3px 0 0;">
                    <h4 class="modal-title">
                        <span class="bi bi-lock"></span>&nbsp;Autorización de descuento requerida
                    </h4>
                </div>
                <div class="modal-body">
                    <table class="table table-sm" style="margin-bottom:6px;">
                        <tbody>
                            <tr style="background:#f9f9f9;">
                                <td style="width:45%; color:#888;">Cajero</td>
                                <td><strong id="da_cashier"><?= esc($current_cashier_name) ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="2"><hr style="margin:4px 0;"></td>
                            </tr>
                            <tr>
                                <td style="color:#888;">Artículo</td>
                                <td><strong id="da_item_name">—</strong></td>
                            </tr>
                            <tr>
                                <td style="color:#888;">Precio unit.</td>
                                <td id="da_unit_price">—</td>
                            </tr>
                            <tr>
                                <td style="color:#888;">Cantidad</td>
                                <td id="da_qty">—</td>
                            </tr>
                            <tr>
                                <td style="color:#888;">Subtotal</td>
                                <td id="da_subtotal">—</td>
                            </tr>
                            <tr>
                                <td style="color:#888;">Descuento</td>
                                <td style="color:#c0392b; font-weight:bold;" id="da_discount">—</td>
                            </tr>
                            <tr style="border-top:2px solid #ddd;">
                                <td style="color:#888; font-weight:bold;">Precio final</td>
                                <td style="color:#27ae60; font-weight:bold; font-size:1.1em;" id="da_final">—</td>
                            </tr>
                            <tr>
                                <td style="color:#888;">Ahorro</td>
                                <td style="color:#e67e22;" id="da_saving">—</td>
                            </tr>
                        </tbody>
                    </table>
                    <div id="da_error" class="text-danger" role="alert" style="min-height:18px; font-size:12px;"></div>
                </div>
                <div class="modal-footer">
                    <button id="da_request_btn" type="button" class="btn btn-warning btn-block">
                        <span class="bi bi-send"></span>&nbsp;Solicitar autorización
                    </button>
                    <button id="da_cancel_btn" type="button" class="btn btn-outline-secondary btn-block" style="margin-top:6px;">
                        Cancelar
                    </button>
                </div>
            </div>

            <!-- State 2: Waiting for admin / enter code -->
            <div id="da_state_waiting" style="display:none;">
                <div class="modal-header" style="background:#f0ad4e; color:#fff; border-radius:3px 3px 0 0;">
                    <h4 class="modal-title">
                        <span class="bi bi-hourglass-split"></span>&nbsp;Esperando aprobación del administrador...
                    </h4>
                </div>
                <div class="modal-body">
                    <div style="background:#f9f9f9; border-radius:4px; padding:10px; margin-bottom:12px;">
                        <table class="table table-sm" style="margin:0;">
                            <tbody>
                                <tr>
                                    <td style="width:45%; color:#888;">Artículo</td>
                                    <td><strong id="da_item_name2"></strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#888;">Descuento</td>
                                    <td style="color:#c0392b; font-weight:bold;" id="da_discount2">—</td>
                                </tr>
                                <tr>
                                    <td style="color:#888;">Precio final</td>
                                    <td style="color:#27ae60; font-weight:bold;" id="da_final2">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align:center; margin-bottom:12px;">
                        <div id="da_status_text" style="font-size:14px; color:#888; margin-bottom:8px;">
                            <span class="bi bi-hourglass-split"></span> Esperando respuesta del administrador...
                        </div>
                        <small style="color:#aaa;">Solicitado hace: <span id="da_elapsed">0s</span></small>
                    </div>
                    <div style="text-align:center;">
                        <p style="color:#555; font-size:13px; margin-bottom:6px;">Código de autorización (4 dígitos):</p>
                        <div style="display:flex; justify-content:center; gap:8px; margin-bottom:8px;">
                            <input type="text" class="da_digit form-control" inputmode="numeric" maxlength="1" autocomplete="off" aria-label="<?= esc(lang('Sales.pin_label')) ?>"
                                   style="width:48px; height:48px; text-align:center; font-size:1.6em; font-weight:bold;" disabled>
                            <input type="text" class="da_digit form-control" inputmode="numeric" maxlength="1" autocomplete="off" aria-label="<?= esc(lang('Sales.pin_label')) ?>"
                                   style="width:48px; height:48px; text-align:center; font-size:1.6em; font-weight:bold;" disabled>
                            <input type="text" class="da_digit form-control" inputmode="numeric" maxlength="1" autocomplete="off" aria-label="<?= esc(lang('Sales.pin_label')) ?>"
                                   style="width:48px; height:48px; text-align:center; font-size:1.6em; font-weight:bold;" disabled>
                            <input type="text" class="da_digit form-control" inputmode="numeric" maxlength="1" autocomplete="off" aria-label="<?= esc(lang('Sales.pin_label')) ?>"
                                   style="width:48px; height:48px; text-align:center; font-size:1.6em; font-weight:bold;" disabled>
                        </div>
                        <div id="da_code_error" class="text-danger" role="alert" style="min-height:18px; font-size:12px;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="da_apply_btn" type="button" class="btn btn-success btn-block" disabled>
                        <span class="bi bi-check-lg"></span>&nbsp;Aplicar código
                    </button>
                    <button id="da_cancel_wait_btn" type="button" class="btn btn-outline-secondary btn-block" style="margin-top:6px;">
                        Cancelar solicitud
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Price Type Authorization Modal -->
<div class="modal fade" id="pa_modal" tabindex="-1" role="dialog" aria-labelledby="pa_title_request" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <!-- Step 1: request authorization -->
            <div id="pa_state_request">
                <div class="modal-header auth-header">
                    <h4 class="modal-title" id="pa_title_request">
                        <span class="bi bi-lock" aria-hidden="true"></span> <?= lang('Sales.price_auth_title') ?>
                    </h4>
                </div>
                <div class="modal-body">
                    <table class="table table-sm auth-summary">
                        <tbody>
                            <tr id="pa_cashier_row">
                                <th scope="row"><?= lang('Sales.price_auth_cashier') ?></th>
                                <td id="pa_cashier"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?= lang('Sales.price_auth_item') ?></th>
                                <td><strong id="pa_item_name">—</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="auth-change">
                        <div class="auth-change-line">
                            <span><span id="pa_old_label"></span>: <strong id="pa_old_price"></strong></span>
                            <span class="bi bi-arrow-right" aria-hidden="true"></span>
                            <span><span id="pa_new_label"></span>: <strong id="pa_new_price_small"></strong></span>
                        </div>
                        <div class="auth-change-new" id="pa_new_price">—</div>
                        <div class="auth-change-diff" id="pa_diff"></div>
                    </div>

                    <p class="auth-help"><?= lang('Sales.price_auth_help') ?></p>
                    <div id="pa_error" class="text-danger" role="alert"></div>
                </div>
                <div class="modal-footer auth-footer">
                    <button id="pa_request_btn" type="button" class="btn btn-primary btn-lg">
                        <span class="bi bi-send" aria-hidden="true"></span> <?= lang('Sales.price_auth_request') ?>
                    </button>
                    <button id="pa_cancel_btn" type="button" class="btn btn-neutral">
                        <?= lang('Sales.price_auth_cancel') ?>
                    </button>
                </div>
            </div>

            <!-- Step 2: waiting for the administrator, then the code -->
            <div id="pa_state_waiting" style="display:none;">
                <div class="modal-header auth-header">
                    <h4 class="modal-title" id="pa_title_waiting">
                        <span class="bi bi-hourglass-split" aria-hidden="true"></span> <?= lang('Sales.price_auth_waiting_title') ?>
                    </h4>
                </div>
                <div class="modal-body">
                    <table class="table table-sm auth-summary">
                        <tbody>
                            <tr>
                                <th scope="row"><?= lang('Sales.price_auth_item') ?></th>
                                <td><strong id="pa_item_name2"></strong></td>
                            </tr>
                            <tr>
                                <th scope="row"><?= lang('Sales.price_auth_price_type') ?></th>
                                <td id="pa_price_type_label2"></td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="auth-elapsed"><?= lang('Sales.price_auth_requested_ago') ?>: <span id="pa_elapsed">0s</span></p>
                    <div id="pa_status" class="auth-status" role="status" aria-live="polite" aria-atomic="true"></div>

                    <p class="auth-code-prompt" id="pa_code_prompt"><?= lang('Sales.price_auth_code_prompt') ?></p>
                    <p class="auth-code-hint" id="pa_code_hint"><?= lang('Sales.price_auth_code_locked') ?></p>
                    <div class="auth-code" role="group" aria-labelledby="pa_code_prompt">
                        <?php for ($digit = 1; $digit <= 4; $digit++) { ?>
                            <input type="text" class="pa_digit auth-digit form-control" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                                   autocomplete="one-time-code" readonly
                                   aria-label="<?= esc(lang('Sales.price_auth_digit', [$digit])) ?>">
                        <?php } ?>
                    </div>
                    <div id="pa_code_error" class="text-danger" role="alert"></div>
                </div>
                <div class="modal-footer auth-footer">
                    <button id="pa_apply_btn" type="button" class="btn btn-primary btn-lg" disabled>
                        <span class="bi bi-check-lg" aria-hidden="true"></span> <?= lang('Sales.price_auth_apply') ?>
                    </button>
                    <button id="pa_cancel_wait_btn" type="button" class="btn btn-neutral">
                        <?= lang('Sales.price_auth_cancel_request') ?>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- PIN Identification Modal -->
<div class="modal fade" id="pin_modal" tabindex="-1" role="dialog"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="pin_modal_label">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pin_modal_label">
                    <span class="bi bi-person" aria-hidden="true"></span> <?= lang('Sales.pin_identify') ?>
                </h5>
                <a href="<?= site_url('home') ?>" class="btn-close" title="<?= lang('Common.home') ?>" aria-label="<?= lang('Common.home') ?>"></a>
            </div>
            <div class="modal-body pin-body">
                <p class="text-muted" id="pin_prompt"><?= lang('Sales.pin_enter_prompt') ?></p>
                <label for="pin_input" class="visually-hidden"><?= lang('Sales.pin_label') ?></label>
                <input type="password" id="pin_input" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="4"
                       autocomplete="off" aria-describedby="pin_prompt pin_error"
                       class="form-control form-control-lg pin-input" placeholder="&bull;&bull;&bull;&bull;">
                <div id="pin_error" class="text-danger pin-error" role="alert"></div>
            </div>
            <div class="modal-footer pin-footer">
                <button type="button" id="pin_submit_btn" class="btn btn-primary btn-lg w-100">
                    <span class="bi bi-check-lg" aria-hidden="true"></span> <?= lang('Sales.pin_enter') ?>
                </button>
                <?php
                $_emp_check = model(\App\Models\Employee::class);
                $_pid_check = session()->get('person_id');
                if ($_emp_check->has_grant('sales_consult_stock', $_pid_check)):
                ?>
                <button type="button" id="pin_stock_consult_btn" class="btn btn-link">
                    <?= lang('Sales.stock_consult') ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div id="register_live" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>

<script type="text/javascript">
    $(document).ready(function() {
        const redirect = function() {
            window.location.href = "<?= site_url('sales'); ?>";
        };

        $("#remove_customer_button").click(function() {
            $.post("<?= site_url('sales/removeCustomer'); ?>", redirect);
        });

        $(".delete_item_button").click(function() {
            const item_id = $(this).data('item-id');
            $.post("<?= site_url('sales/deleteItem/'); ?>" + item_id, redirect);
        });

        $(".delete_payment_button").click(function() {
            const item_id = $(this).data('payment-id');
            $.post("<?= site_url('sales/deletePayment/'); ?>" + item_id, redirect);
        });

        $("input[name='item_number']").change(function() {
            var item_id = $(this).parents('tr').find("input[name='item_id']").val();
            var item_number = $(this).val();
            $.ajax({
                url: "<?= site_url('sales/changeItemNumber') ?>",
                method: 'post',
                data: {
                    'item_id': item_id,
                    'item_number': item_number,
                },
                dataType: 'json'
            });
        });

        $("input[name='name']").change(function() {
            var item_id = $(this).parents('tr').find("input[name='item_id']").val();
            var item_name = $(this).val();
            $.ajax({
                url: "<?= site_url('sales/changeItemName') ?>",
                method: 'post',
                data: {
                    'item_id': item_id,
                    'item_name': item_name,
                },
                dataType: 'json'
            });
        });

        $("input[name='item_description']").change(function() {
            var item_id = $(this).parents('tr').find("input[name='item_id']").val();
            var item_description = $(this).val();
            $.ajax({
                url: "<?= site_url('sales/changeItemDescription') ?>",
                method: 'post',
                data: {
                    'item_id': item_id,
                    'item_description': item_description,
                },
                dataType: 'json'
            });
        });

        <?php if ($current_cashier_id > 0): ?>
        $('#item').trigger('focus');
        <?php endif; ?>

        $('#item').autocomplete({
            source: "<?= esc("$controller_name/itemSearch") ?>",
            minChars: 0,
            autoFocus: false,
            delay: 500,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $('#add_item_form').submit();
                return false;
            }
        });

        $('#item').keypress(function(e) {
            if (e.which == 13) {
                $('#add_item_form').submit();
                return false;
            }
        });

        $('#item, #customer').dblclick(function() {
            $(this).autocomplete('search');
        });

        $('#customer').autocomplete({
            source: "<?= site_url('customers/suggest') ?>",
            minChars: 0,
            delay: 10,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $('#select_customer_form').submit();
                return false;
            }
        });

        $('#customer').keypress(function(e) {
            if (e.which == 13) {
                $('#select_customer_form').submit();
                return false;
            }
        });

        $('.giftcard-input').autocomplete({
            source: "<?= site_url('giftcards/suggest') ?>",
            minChars: 0,
            delay: 10,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $('#add_payment_form').submit();
                return false;
            }
        });

        $('#comment').keyup(function() {
            $.post("<?= esc(site_url("$controller_name/setComment")) ?>", {
                comment: $('#comment').val()
            });
        });

        <?php if ($config['invoice_enable']) { ?>
            $('#sales_invoice_number').keyup(function() {
                $.post("<?= esc(site_url("$controller_name/setInvoiceNumber")) ?>", {
                    sales_invoice_number: $('#sales_invoice_number').val()
                });
            });

        <?php } ?>

        $('#sales_print_after_sale').change(function() {
            $.post("<?= esc(site_url("$controller_name/setPrintAfterSale")) ?>", {
                sales_print_after_sale: $(this).is(':checked')
            });
        });

        $('#price_work_orders').change(function() {
            $.post("<?= esc(site_url("$controller_name/setPriceWorkOrders")) ?>", {
                price_work_orders: $(this).is(':checked')
            });
        });

        $('#email_receipt').change(function() {
            $.post("<?= esc(site_url("$controller_name/setEmailReceipt")) ?>", {
                email_receipt: $(this).is(':checked')
            });
        });

        $('#finish_sale_button').click(function() {
            registerResetCount();
            $('#buttons_form').attr('action', "<?= "$controller_name/complete" ?>");
            $('#buttons_form').submit();
        });

        $('#finish_invoice_quote_button').click(function() {
            registerResetCount();
            $('#buttons_form').attr('action', "<?= "$controller_name/complete" ?>");
            $('#buttons_form').submit();
        });

        $('#suspend_sale_button').click(function() {
            registerResetCount();
            $('#buttons_form').attr('action', "<?= site_url("$controller_name/suspend") ?>");
            $('#buttons_form').submit();
        });

        $('#cancel_sale_button').click(function() {
            registerResetCount();
            if (confirm("<?= lang(ucfirst($controller_name) . '.confirm_cancel_sale') ?>")) {
                $('#buttons_form').attr('action', "<?= site_url("$controller_name/cancel") ?>");
                $('#buttons_form').submit();
            }
        });

        $('#add_payment_button').click(function() {
            $('#add_payment_form').submit();
        });

        $('#payment_types').change(check_payment_type).ready(check_payment_type);

        $('#cart_contents input').keypress(function(event) {
            if (event.which == 13) {
                $(this).parents('tr').prevAll('form:first').submit();
            }
        });

        $('#amount_tendered, #giftcard_number').keypress(function(event) {
            if (event.which == 13) {
                $('#add_payment_form').submit();
            }
        });

        dialog_support.init('a.modal-dlg, button.modal-dlg');

        // Suspended sales modal: own opener so focus starts on the first "Resume" and returns to this button
        $('#show_suspended_sales_button').on('click', function() {
            var trigger = this;
            var $content = $('<div></div>');
            var loaded = false;
            var shown = false;
            var focusInitial = function(dialog) {
                if (!loaded || !shown) {
                    return;
                }
                var $first = $content.find('.suspended-resume').first();
                ($first.length ? $first : dialog.getModalFooter().find('button').first()).trigger('focus');
            };

            window.registerFocusReturn = trigger;
            var dialog = BootstrapDialog.show({
                title: <?= json_encode(lang('Sales.suspended_sales_title')) ?>,
                cssClass: 'suspended-dlg',
                message: $content,
                buttons: [{
                    id: 'suspended_close',
                    label: lang.line('common_close'),
                    cssClass: 'btn-outline-secondary',
                    action: function(dlg) { dlg.close(); }
                }],
                onshown: function(dlg) {
                    shown = true;
                    focusInitial(dlg);
                }
            });
            $.get($(trigger).data('href'), function(html) {
                $content.html(html);
                loaded = true;
                focusInitial(dialog);
            });
            return false;
        });

        table_support.handle_submit = function(resource, response, stay_open) {
            $.notify({
                message: response.message
            }, {
                type: response.success ? 'success' : 'danger'
            })

            if (response.success) {
                if (resource.match(/customers$/)) {
                    $('#customer').val(response.id);
                    $('#select_customer_form').submit();
                } else {
                    var $stock_location = $("select[name='stock_location']").val();
                    $('#item_location').val($stock_location);
                    $('#item').val(response.id);
                    if (stay_open) {
                        $('#add_item_form').ajaxSubmit();
                        $('#item').trigger('focus');
                    } else {
                        $('#add_item_form').submit();
                    }
                }
            }
        }

        $('[name="price"],[name="quantity"],[name="description"],[name="serialnumber"],[name="discounted_total"]').change(function() {
            $(this).parents('tr').prevAll('form:first').submit()
        });

        // Column titles as data-label so the narrow (card) layout can show them next to each value
        var cartLabels = $('#register thead th').map(function() { return $.trim($(this).text()); }).get();
        $('#cart_contents tr').each(function() {
            var $cells = $(this).children('td');
            if ($cells.length === 8) {
                $cells.each(function(index) { $(this).attr('data-label', cartLabels[index]); });
            }
            else {
                // description/serial line: flag empty spacer cells so the narrow layout can hide them
                $cells.each(function() {
                    if ($.trim($(this).text()) === '' && !$(this).find('input:not([type="hidden"])').length) {
                        $(this).addClass('cell-empty');
                    }
                });
            }
        });

        // Segmented controls drive the (hidden) native price_type select and discount_toggle checkbox
        var segmentedSet = function($group, value) {
            $group.find('.segmented-option').each(function() {
                var on = String($(this).data('value')) === String(value);
                $(this).toggleClass('active', on).attr('aria-pressed', on ? 'true' : 'false');
            });
        };

        $(document).on('click', '.price-type .segmented-option', function() {
            var $select = $(this).closest('td').find('[name="price_type"]');
            segmentedSet($(this).closest('.segmented'), $(this).data('value'));
            $select.val($(this).data('value')).trigger('change');
        });

        $(document).on('click', '.discount-type .segmented-option', function() {
            var $toggle = $(this).closest('td').find('[name="discount_toggle"]');
            segmentedSet($(this).closest('.segmented'), $(this).data('value'));
            $toggle.prop('checked', String($(this).data('value')) === '1').trigger('change');
        });

        $('[name="price_type"]').change(function() {
            $(this).parents('tr').prevAll('form:first').submit();
        });

        // Discount field: Enter key triggers auth or submit (no auto-submit on blur)
        $('[name="discount"]').keydown(function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $(this).closest('tr').prevAll('form:first').submit();
            }
        });

        $('[name="discount_toggle"]').change(function() {
            var line  = $(this).attr('data-line');
            var $form = $('#cart_' + line);
            var input = $('<input>').attr('type', 'hidden').attr('name', 'discount_type').val($(this).prop('checked') ? 1 : 0);
            $form.find('[name="discount_type"]').remove();
            $form.append(input);
            $form.submit();
        });

        // Cart form submit interceptor — shows auth modal when price type or discount changed
        $(document).on('submit', '[id^="cart_"]', function(e) {
            var $form = $(this);
            var $row  = $form.nextAll('tr:first');

            if ($form.find('[name="price_approval_id"]').val() > 0) {
                // Price authorization already applied for this submit — fall through to discount check.
            } else {
                var $ptSelect = $row.find('[name="price_type"]');
                if ($ptSelect.length) {
                    var newPriceType = parseInt($ptSelect.val(), 10) || 0;
                    var origPriceType = parseInt($ptSelect.data('original') || '0', 10);
                    if (newPriceType > 0 && newPriceType !== origPriceType) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        priceAuthOpen($form, $row, $ptSelect, newPriceType);
                        return false;
                    }
                }
            }

            if ($form.find('[name="approval_id"]').val() > 0) return true;

            var $discInput = $row.find('[name="discount"]');
            if (!$discInput.length) return true;

            var discountVal  = discountParseFloat($discInput.val());
            var originalVal  = discountParseFloat($discInput.data('original') || '0');
            var discountType = $row.find('[name="discount_toggle"]').prop('checked') ? 1 : 0;
            var dtHidden     = $form.find('[name="discount_type"]');
            if (dtHidden.length && dtHidden.val() !== undefined) {
                discountType = parseInt(dtHidden.val(), 10) || discountType;
            }
            var originalType = parseInt($discInput.data('original-type') || '0', 10);
            var changed = Math.abs(discountVal - originalVal) > 0.005 || discountType !== originalType;

            if (discountVal > 0 && changed) {
                e.preventDefault();
                e.stopImmediatePropagation();
                discountAuthOpen($form, $row, $discInput.val(), discountVal, discountType);
                return false;
            }
        });
    });

    function check_payment_type() {
        var cash_mode = <?= json_encode($cash_mode) ?>;

        if ($("#payment_types").val() == "<?= lang(ucfirst($controller_name) . '.giftcard') ?>") {
            $("#sale_total").html("<?= to_currency($total) ?>");
            $("#sale_amount_due").html("<?= to_currency($amount_due) ?>");
            $("#amount_tendered_label").html("<?= lang(ucfirst($controller_name) . '.giftcard_number') ?>").attr('for', 'giftcard_number');
            $("#amount_tendered:enabled").val('').focus();
            $(".giftcard-input").attr('disabled', false);
            $(".non-giftcard-input").attr('disabled', true);
            $(".giftcard-input:enabled").val('').focus();
        } else if (($("#payment_types").val() == "<?= lang(ucfirst($controller_name) . '.cash') ?>" && cash_mode == '1')) {
            $("#sale_total").html("<?= to_currency($non_cash_total) ?>");
            $("#sale_amount_due").html("<?= to_currency($cash_amount_due) ?>");
            $("#amount_tendered_label").html("<?= lang(ucfirst($controller_name) . '.amount_tendered') ?>").attr('for', 'amount_tendered');
            $("#amount_tendered:enabled").val("<?= to_currency_no_money($cash_amount_due) ?>");
            $(".giftcard-input").attr('disabled', true);
            $(".non-giftcard-input").attr('disabled', false);
        } else {
            $("#sale_total").html("<?= to_currency($non_cash_total) ?>");
            $("#sale_amount_due").html("<?= to_currency($amount_due) ?>");
            $("#amount_tendered_label").html("<?= lang(ucfirst($controller_name) . '.amount_tendered') ?>").attr('for', 'amount_tendered');
            $("#amount_tendered:enabled").val("<?= to_currency_no_money($amount_due) ?>");
            $(".giftcard-input").attr('disabled', true);
            $(".non-giftcard-input").attr('disabled', false);
        }
    }

    // Add Keyboard Shortcuts/Hotkeys to Sale Register (listed in the "Atajos" dialog).
    // Esc cancels the sale only when it was not used to close a dialog, a suggestion list or a dropdown,
    // or to back out of the amount tendered field.
    var escUsed = false;
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.keyCode >= 49 && e.keyCode <= 57) { // Alt+1..9 are shortcuts, never text
            e.preventDefault();
        }
        if (e.key !== 'Escape') {
            return;
        }
        escUsed = $('.modal.show').length > 0 || $('.ui-autocomplete:visible').length > 0 || $('.bootstrap-select.show').length > 0;
        if (e.target && e.target.id === 'amount_tendered') {
            escUsed = true;
            check_payment_type();
            $('#item').trigger('focus');
        }
    }, true);

    document.body.onkeyup = function(e) {
        if (e.altKey) {
            switch (e.keyCode) {
                case 49: // Alt + 1 Items Seach
                    $("#item").focus();
                    $("#item").select();
                    break;
                case 50: // Alt + 2 Customers Search
                    $("#customer").focus();
                    $("#customer").select();
                    break;
                case 51: // Alt + 3 Suspend Current Sale
                    $("#suspend_sale_button").click();
                    break;
                case 52: // Alt + 4 Check Suspended
                    $("#show_suspended_sales_button").click();
                    break;
                case 53: // Alt + 5 Edit Amount Tendered Value
                    $("#amount_tendered").focus();
                    $("#amount_tendered").select();
                    break;
                case 54: // Alt + 6 Add Payment
                    $("#add_payment_button").click();
                    break;
                case 55: // Alt + 7 Add Payment and Complete Sales/Invoice
                    $("#add_payment_button").click();
                    window.location.href = "<?= 'sales/complete' ?>";
                    break;
                case 56: // Alt + 8 Finish Quote/Invoice without payment
                    $("#finish_invoice_quote_button").click();
                    break;
                case 57: // Alt + 9 Open Shortcuts Help Modal
                    $("#show_keyboard_help").click();
                    break;
            }
        }

        if (e.keyCode === 27 && !escUsed && !$('.modal.show').length) { // ESC Cancel Current Sale
            $("#cancel_sale_button").click();
        }
        escUsed = false;
    }

    // Cart count survives the page reload so the change can be announced to screen readers
    function registerResetCount() {
        try {
            sessionStorage.setItem('register_cart_count', '0');
            sessionStorage.removeItem('register_sale_channel');
        } catch (err) { /* storage blocked */ }
    }

    // ─── Discount Authorization ────────────────────────────────────────────────
    var _daApprovalId  = null;
    var _daPendingForm = null;
    var _daPollTimer   = null;
    var _daElapsedTimer = null;
    var _daElapsed     = 0;
    var _daRawDiscount = '';

    function discountParseFloat(s) {
        if (!s) return 0;
        s = String(s).trim();
        var commaPos = s.lastIndexOf(',');
        var dotPos   = s.lastIndexOf('.');
        if (commaPos > dotPos) {
            // Comma is decimal separator (e.g. "1.000,50" or "500,00")
            s = s.replace(/\./g, '').replace(',', '.');
        } else if (dotPos > commaPos) {
            var afterDot = s.substring(dotPos + 1);
            var dotCount = (s.match(/\./g) || []).length;
            if (dotCount > 1 || afterDot.length === 3) {
                // Dots are thousands separators (e.g. "1.000.000")
                s = s.replace(/\./g, '');
            } else {
                // Single dot is decimal separator (e.g. "1000.50")
                s = s.replace(/,/g, '');
            }
        }
        return parseFloat(s) || 0;
    }

    function discountFmtNum(n) {
        return Math.round(n).toLocaleString();
    }

    function discountFmtMoney(n) {
        return '<?= esc($config['currency_symbol']) ?> ' + discountFmtNum(n);
    }

    function discountAuthOpen($form, $row, rawDiscount, discountVal, discountType) {
        _daPendingForm = $form;
        _daApprovalId  = null;
        _daRawDiscount = rawDiscount;

        var itemName   = $row.find('td:nth-child(3)').text().split('[')[0].trim().replace(/\s+/g, ' ');
        var itemPrice  = discountParseFloat($row.find('[name="price"]').val());
        var itemQty    = discountParseFloat($row.find('[name="quantity"]').val()) || 1;
        var locationId = parseInt($row.find('[name="location"]').val(), 10) || 0;

        var subtotal   = itemPrice * itemQty;
        var discAmount, discLabel;
        if (discountType === 1) {
            discAmount = discountVal * itemQty;
            discLabel  = discountFmtMoney(discountVal) + ' c/u';
        } else {
            discAmount = subtotal * discountVal / 100;
            discLabel  = discountVal.toFixed(1) + '%';
        }
        var finalPrice = subtotal - discAmount;

        $('#da_cashier').text(window.registerCashierName || '');
        $('#da_item_name').text(itemName || '—');
        $('#da_unit_price').text(discountFmtMoney(itemPrice));
        $('#da_qty').text(itemQty % 1 === 0 ? itemQty : itemQty.toFixed(2));
        $('#da_subtotal').text(discountFmtMoney(subtotal));
        $('#da_discount').text('−' + discountFmtMoney(discAmount) + ' (' + discLabel + ')');
        $('#da_final').text(discountFmtMoney(finalPrice));
        $('#da_saving').text(discountFmtMoney(discAmount));

        $('#da_item_name2').text(itemName || '—');
        $('#da_discount2').text('−' + discountFmtMoney(discAmount) + ' (' + discLabel + ')');
        $('#da_final2').text(discountFmtMoney(finalPrice));

        $('#da_modal')
            .data('discount_raw', rawDiscount)
            .data('discount_type', discountType)
            .data('location_id', locationId)
            .data('item_name', itemName)
            .data('item_price', itemPrice)
            .data('item_qty', itemQty);

        $('#da_state_request').show();
        $('#da_state_waiting').hide();
        $('#da_error').text('');
        $('#da_code_error').text('');
        $('#da_status_text').html('<span class="bi bi-hourglass-split"></span> Esperando respuesta del administrador...');
        $('#da_status_text').css('color', '#888');
        $('.da_digit').val('').prop('disabled', true);
        $('#da_apply_btn').prop('disabled', true).html('<span class="bi bi-check-lg"></span>&nbsp;Aplicar código');
        $('#da_request_btn').prop('disabled', false).html('<span class="bi bi-send"></span>&nbsp;Solicitar autorización');
        $('#da_elapsed').text('0s');

        $('#da_modal').modal('show');
    }

    function discountAuthStopTimers() {
        if (_daPollTimer)    clearInterval(_daPollTimer);
        if (_daElapsedTimer) clearInterval(_daElapsedTimer);
        _daElapsed = 0;
    }

    function discountAuthStartPolling() {
        discountAuthStopTimers();
        _daElapsedTimer = setInterval(function() {
            _daElapsed++;
            var m = Math.floor(_daElapsed / 60), s = _daElapsed % 60;
            $('#da_elapsed').text((m > 0 ? m + 'm ' : '') + s + 's');
        }, 1000);
        _daPollTimer = setInterval(function() {
            if (!_daApprovalId) return;
            $.ajax({
                url: '<?= site_url('sales/discountPoll') ?>',
                type: 'POST',
                data: { approval_id: _daApprovalId },
                dataType: 'json',
                success: function(res) {
                    if (!res.success) return;
                    if (res.status === 'approved') {
                        clearInterval(_daPollTimer);
                        $('#da_status_text').html('<span style="color:#27ae60;"><span class="bi bi-check-circle"></span>&nbsp;¡Aprobado! Ingrese el código:</span>');
                        $('#da_status_text').css('color', '#27ae60');
                        $('.da_digit').prop('disabled', false);
                        $('.da_digit:first').focus();
                        $('#da_apply_btn').prop('disabled', false);
                    } else if (res.status === 'expired') {
                        clearInterval(_daPollTimer);
                        $('#da_status_text').text('Solicitud rechazada o expirada.');
                        $('#da_status_text').css('color', '#c0392b');
                        setTimeout(function() { $('#da_modal').modal('hide'); }, 2500);
                    }
                }
            });
        }, 3000);
    }

    $(document).ready(function() {
        $('#da_request_btn').on('click', function() {
            var $m = $('#da_modal');
            $(this).prop('disabled', true).html('<span class="bi bi-hourglass-split"></span> Enviando...');
            $.ajax({
                url: '<?= site_url('sales/discountRequest') ?>',
                type: 'POST',
                data: {
                    discount:      $m.data('discount_raw'),
                    discount_type: $m.data('discount_type'),
                    location_id:   $m.data('location_id'),
                    item_name:     $m.data('item_name'),
                    item_price:    $m.data('item_price'),
                    item_quantity: $m.data('item_qty')
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        _daApprovalId = res.approval_id;
                        $('#da_state_request').hide();
                        $('#da_state_waiting').show();
                        discountAuthStartPolling();
                    } else {
                        $('#da_error').text(res.message || 'Error al enviar solicitud');
                        $('#da_request_btn').prop('disabled', false).html('<span class="bi bi-send"></span>&nbsp;Solicitar autorización');
                    }
                },
                error: function() {
                    $('#da_error').text('Error de conexión');
                    $('#da_request_btn').prop('disabled', false).html('<span class="bi bi-send"></span>&nbsp;Solicitar autorización');
                }
            });
        });

        $('#da_cancel_btn, #da_cancel_wait_btn').on('click', function() {
            discountAuthStopTimers();
            if (_daPendingForm) {
                var $row = _daPendingForm.nextAll('tr:first');
                var $di  = $row.find('[name="discount"]');
                $di.val($di.data('original'));
                var originalType = String($di.data('original-type') || 0);
                $row.find('[name="discount_toggle"]').prop('checked', originalType === '1');
                $row.find('.discount-type .segmented-option').each(function() {
                    $(this).toggleClass('active', String($(this).data('value')) === originalType);
                });
            }
            _daApprovalId  = null;
            _daPendingForm = null;
            $('#da_modal').modal('hide');
        });

        // 4-digit inputs: auto-advance + backspace
        $(document).on('input', '.da_digit', function() {
            var val = $(this).val().replace(/\D/g, '').slice(0, 1);
            $(this).val(val);
            if (val.length === 1) {
                var $next = $(this).next('.da_digit');
                if ($next.length) $next.focus(); else $('#da_apply_btn').focus();
            }
        });
        $(document).on('keydown', '.da_digit', function(e) {
            if (e.which === 8 && $(this).val() === '') $(this).prev('.da_digit').focus();
        });

        $('#da_apply_btn').on('click', function() {
            var code = $('.da_digit').map(function() { return $(this).val(); }).get().join('');
            if (code.length !== 4 || !_daApprovalId) return;
            var $m = $('#da_modal');
            $(this).prop('disabled', true).html('<span class="bi bi-hourglass-split"></span> Verificando...');
            $.ajax({
                url: '<?= site_url('sales/discountVerify') ?>',
                type: 'POST',
                data: {
                    approval_id:   _daApprovalId,
                    code:          code,
                    discount:      $m.data('discount_raw'),
                    discount_type: $m.data('discount_type')
                },
                dataType: 'json',
                success: function(res) {
                    if (res.valid) {
                        discountAuthStopTimers();
                        var $f = _daPendingForm;
                        var savedId   = _daApprovalId;
                        var savedType = $m.data('discount_type');
                        $f.find('[name="approval_id"]').remove();
                        $f.find('[name="approval_code"]').remove();
                        $f.find('[name="discount_type"]').remove();
                        $f.append($('<input type="hidden" name="approval_id">').val(savedId));
                        $f.append($('<input type="hidden" name="approval_code">').val(code));
                        $f.append($('<input type="hidden" name="discount_type">').val(savedType));
                        _daApprovalId  = null;
                        _daPendingForm = null;
                        $('#da_modal').modal('hide');
                        $f.submit();
                    } else {
                        $('#da_code_error').text(res.message || 'Código incorrecto');
                        $('.da_digit').val('').first().focus();
                        $('#da_apply_btn').prop('disabled', false).html('<span class="bi bi-check-lg"></span>&nbsp;Aplicar código');
                    }
                },
                error: function() {
                    $('#da_code_error').text('Error de conexión');
                    $('#da_apply_btn').prop('disabled', false).html('<span class="bi bi-check-lg"></span>&nbsp;Aplicar código');
                }
            });
        });
    });
    // ─── End Discount Authorization ────────────────────────────────────────────

    // ─── Price Type Authorization ───────────────────────────────────────────────
    // UI only: the request / poll / verify endpoints and their order are unchanged.
    var _paApprovalId    = null;
    var _paPendingForm   = null;
    var _paPendingSelect = null;
    var _paPollTimer     = null;
    var _paElapsedTimer  = null;
    var _paElapsed       = 0;
    var _paApproved      = false;
    var _paOriginal      = null; // {type, price} of the line before the change was requested

    var PA = <?= json_encode([
        'sending'    => lang('Sales.price_auth_sending'),
        'verifying'  => lang('Sales.price_auth_verifying'),
        'connection' => lang('Sales.price_auth_connection_error'),
        'sendError'  => lang('Sales.price_auth_send_error'),
        'approved'   => lang('Sales.price_auth_approved'),
        'expired'    => lang('Sales.price_auth_expired'),
        'slow'       => lang('Sales.price_auth_slow'),
        'wrong'      => lang('Sales.price_auth_wrong_code'),
        'authorized' => lang('Sales.price_auth_authorized'),
        'kept'       => lang('Sales.price_auth_kept'),
        'difference' => lang('Sales.price_auth_difference'),
        'request'    => lang('Sales.price_auth_request'),
        'apply'      => lang('Sales.price_auth_apply'),
    ], JSON_UNESCAPED_UNICODE) ?>;

    var PRICE_TYPE_LABELS = {
        0: <?= json_encode(lang('Sales.price_type_sale_short')) ?>,
        1: <?= json_encode(lang('Sales.price_type_wholesale')) ?>,
        2: <?= json_encode(lang('Sales.price_type_reseller')) ?>
    };

    var PA_ICONS = {ok: 'check-circle', error: 'x-circle', info: 'info-circle'};

    function paRequestLabel() {
        return '<span class="bi bi-send" aria-hidden="true"></span> ' + PA.request;
    }

    function paApplyLabel() {
        return '<span class="bi bi-check-lg" aria-hidden="true"></span> ' + PA.apply;
    }

    // Status line (also the polite live region); icon + text, never colour alone
    function paSay(text, kind) {
        var $status = $('#pa_status').empty();
        if (!text) {
            return;
        }
        $('<span class="auth-status-' + kind + '"></span>')
            .append('<span class="bi bi-' + PA_ICONS[kind] + '" aria-hidden="true"></span> ')
            .append(document.createTextNode(text))
            .appendTo($status);
    }

    function paCode() {
        return $('.pa_digit').map(function() { return $(this).val(); }).get().join('');
    }

    // "Apply" stays disabled until the administrator approved and the 4 digits are in
    function paUpdateApply() {
        $('#pa_apply_btn').prop('disabled', !(_paApproved && paCode().length === 4));
    }

    function paLockDigits(locked) {
        $('.pa_digit').prop('readonly', locked).attr('aria-readonly', locked ? 'true' : 'false');
        $('#pa_code_hint').prop('hidden', !locked);
    }

    function priceAuthOpen($form, $row, $select, priceType) {
        _paPendingForm   = $form;
        _paPendingSelect = $select;
        _paApprovalId    = null;
        _paApproved      = false;
        window.registerFocusReturn = document.activeElement;

        var $price     = $row.find('[name="price"]');
        var origType   = parseInt($select.data('original') || '0', 10);
        _paOriginal    = {type: origType, price: $price.val()};

        var itemName   = $row.find('td:nth-child(3)').text().split('[')[0].trim().replace(/\s+/g, ' ');
        var itemQty    = discountParseFloat($row.find('[name="quantity"]').val()) || 1;
        var locationId = parseInt($row.find('[name="location"]').val(), 10) || 0;
        var oldPrice   = discountParseFloat($price.val());
        var newPrice   = priceType === 1
            ? discountParseFloat($select.data('wholesale'))
            : discountParseFloat($select.data('reseller'));
        var oldLabel   = PRICE_TYPE_LABELS[origType] || PRICE_TYPE_LABELS[0];
        var newLabel   = PRICE_TYPE_LABELS[priceType] || '?';
        var diff       = newPrice - oldPrice;

        $('#pa_cashier').text(window.registerCashierName || '');
        $('#pa_cashier_row').prop('hidden', !window.registerCashierName);
        $('#pa_item_name, #pa_item_name2').text(itemName || '—');
        $('#pa_old_label').text(oldLabel);
        $('#pa_new_label').text(newLabel);
        $('#pa_old_price').text(discountFmtMoney(oldPrice));
        $('#pa_new_price_small').text(discountFmtMoney(newPrice));
        $('#pa_new_price').text(discountFmtMoney(newPrice));
        $('#pa_diff').text(diff === 0 ? '' : PA.difference + ': ' + (diff < 0 ? '−' : '+') + discountFmtMoney(Math.abs(diff)));
        $('#pa_price_type_label2').text(newLabel);

        $('#pa_modal')
            .data('price_type', priceType)
            .data('location_id', locationId)
            .data('item_name', itemName)
            .data('item_price', newPrice)
            .data('item_qty', itemQty);

        $('#pa_state_request').show();
        $('#pa_state_waiting').hide();
        $('#pa_error, #pa_code_error').text('');
        paSay('', 'info');
        $('.pa_digit').val('');
        paLockDigits(true);
        paUpdateApply();
        $('#pa_request_btn').prop('disabled', false).html(paRequestLabel());
        $('#pa_elapsed').text('0s');

        $('#pa_modal').modal('show');
    }

    function priceAuthStopTimers() {
        if (_paPollTimer)    clearInterval(_paPollTimer);
        if (_paElapsedTimer) clearInterval(_paElapsedTimer);
        _paElapsed = 0;
    }

    // Back to the price the line had before: type, segmented control and price field, plus a short notice
    function priceAuthRestore() {
        if (_paPendingSelect && _paOriginal) {
            _paPendingSelect.val(_paPendingSelect.data('original'));
            _paPendingSelect.closest('td').find('.price-type .segmented-option').each(function() {
                var on = String($(this).data('value')) === String(_paOriginal.type);
                $(this).toggleClass('active', on).attr('aria-pressed', on ? 'true' : 'false');
            });
            _paPendingSelect.closest('td').find('[name="price"]').val(_paOriginal.price);

            var kept = PA.kept.replace('{0}', PRICE_TYPE_LABELS[_paOriginal.type] || PRICE_TYPE_LABELS[0]);
            $.notify(kept, {type: 'info'});
            window.registerAnnounce && window.registerAnnounce(kept);
        }
    }

    function priceAuthCancel() {
        priceAuthStopTimers();
        priceAuthRestore();
        _paApprovalId    = null;
        _paApproved      = false;
        _paPendingForm   = null;
        _paPendingSelect = null;
        $('#pa_modal').modal('hide');
    }

    function priceAuthStartPolling() {
        priceAuthStopTimers();
        _paElapsedTimer = setInterval(function() {
            _paElapsed++;
            var m = Math.floor(_paElapsed / 60), sec = _paElapsed % 60;
            $('#pa_elapsed').text((m > 0 ? m + 'm ' : '') + sec + 's');
            if (_paElapsed === 120 && !_paApproved) {
                paSay(PA.slow, 'info');
            }
        }, 1000);
        _paPollTimer = setInterval(function() {
            if (!_paApprovalId) return;
            $.ajax({
                url: '<?= site_url('sales/discountPoll') ?>',
                type: 'POST',
                data: { approval_id: _paApprovalId },
                dataType: 'json',
                success: function(res) {
                    if (!res.success) return;
                    if (res.status === 'approved') {
                        clearInterval(_paPollTimer);
                        _paApproved = true;
                        paSay(PA.approved, 'ok');
                        paLockDigits(false);
                        $('.pa_digit:first').trigger('focus');
                        paUpdateApply();
                    } else if (res.status === 'expired') {
                        clearInterval(_paPollTimer);
                        paSay(PA.expired, 'error');
                        setTimeout(priceAuthCancel, 2500);
                    }
                }
            });
        }, 3000);
    }

    $(document).ready(function() {
        $('#pa_request_btn').on('click', function() {
            var $m = $('#pa_modal');
            $(this).prop('disabled', true).html('<span class="bi bi-hourglass-split" aria-hidden="true"></span> ' + PA.sending);
            $.ajax({
                url: '<?= site_url('sales/priceRequest') ?>',
                type: 'POST',
                data: {
                    price_type:    $m.data('price_type'),
                    location_id:   $m.data('location_id'),
                    item_name:     $m.data('item_name'),
                    item_price:    $m.data('item_price'),
                    item_quantity: $m.data('item_qty')
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        _paApprovalId = res.approval_id;
                        $('#pa_state_request').hide();
                        $('#pa_state_waiting').show();
                        $('.pa_digit:first').trigger('focus');
                        priceAuthStartPolling();
                    } else {
                        $('#pa_error').text(res.message || PA.sendError);
                        $('#pa_request_btn').prop('disabled', false).html(paRequestLabel());
                    }
                },
                error: function() {
                    $('#pa_error').text(PA.connection);
                    $('#pa_request_btn').prop('disabled', false).html(paRequestLabel());
                }
            });
        });

        $('#pa_cancel_btn, #pa_cancel_wait_btn').on('click', priceAuthCancel);

        // Code boxes: digits only, auto-advance, Backspace goes back, paste fills all four
        $(document).on('input', '.pa_digit', function() {
            var val = $(this).val().replace(/\D/g, '').slice(0, 1);
            $(this).val(val);
            if (val.length === 1) {
                var $next = $(this).next('.pa_digit');
                $next.length && $next.trigger('focus');
            }
            paUpdateApply();
            if (paCode().length === 4 && !$('#pa_apply_btn').prop('disabled')) {
                $('#pa_apply_btn').trigger('click');
            }
        });
        $(document).on('keydown', '.pa_digit', function(e) {
            if (e.which === 8 && $(this).val() === '') {
                $(this).prev('.pa_digit').val('').trigger('focus');
                paUpdateApply();
            }
        });
        $(document).on('paste', '.pa_digit', function(e) {
            var data = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            var digits = String(data).replace(/\D/g, '').slice(0, 4);
            if (!digits) {
                return;
            }
            e.preventDefault();
            $('.pa_digit').each(function(index) { $(this).val(digits.charAt(index)); });
            $('.pa_digit').eq(Math.min(digits.length, 4) - 1).trigger('focus');
            paUpdateApply();
            if (digits.length === 4 && !$('#pa_apply_btn').prop('disabled')) {
                $('#pa_apply_btn').trigger('click');
            }
        });

        $('#pa_apply_btn').on('click', function() {
            var code = paCode();
            if (code.length !== 4 || !_paApprovalId) return;
            var $m = $('#pa_modal');
            $(this).prop('disabled', true).html('<span class="bi bi-hourglass-split" aria-hidden="true"></span> ' + PA.verifying);
            $.ajax({
                url: '<?= site_url('sales/priceVerify') ?>',
                type: 'POST',
                data: {
                    approval_id: _paApprovalId,
                    code:        code,
                    price_type:  $m.data('price_type')
                },
                dataType: 'json',
                success: function(res) {
                    if (res.valid) {
                        priceAuthStopTimers();
                        paSay(PA.authorized, 'ok');
                        var $f = _paPendingForm;
                        var savedId = _paApprovalId;
                        $f.find('[name="price_approval_id"]').remove();
                        $f.find('[name="price_approval_code"]').remove();
                        $f.append($('<input type="hidden" name="price_approval_id">').val(savedId));
                        $f.append($('<input type="hidden" name="price_approval_code">').val(code));
                        _paApprovalId    = null;
                        _paPendingForm   = null;
                        _paPendingSelect = null;
                        $('#pa_modal').modal('hide');
                        $f.submit();
                    } else {
                        $('#pa_code_error').text(PA.wrong);
                        $('.pa_digit').val('').first().trigger('focus');
                        $('#pa_apply_btn').html(paApplyLabel());
                        paUpdateApply();
                    }
                },
                error: function() {
                    $('#pa_code_error').text(PA.connection);
                    $('#pa_apply_btn').html(paApplyLabel());
                    paUpdateApply();
                }
            });
        });
    });
    // ─── End Price Type Authorization ──────────────────────────────────────────

    // Focus, announcements and Enter/Esc in dialogs
    $(document).ready(function() {
        var $live = $('#register_live');
        var announce = function(message) {
            $live.text('');
            setTimeout(function() { $live.text(message); }, 60);
        };
        window.registerAnnounce = announce;

        // Back to the search box whenever the last dialog closes
        $(document).on('hidden.bs.modal', function() {
            if (!$('.modal.show').length) {
                var target = window.registerFocusReturn;
                window.registerFocusReturn = null;
                setTimeout(function() {
                    var $target = target && document.contains(target) ? $(target) : $('#item');
                    $target.trigger('focus');
                }, 50);
            }
        });

        // Added / removed item, announced after the reload
        var count = $('#cart_contents form[id^="cart_"]').length;
        var previous = null;
        try { previous = sessionStorage.getItem('register_cart_count'); } catch (err) { /* storage blocked */ }
        try { sessionStorage.setItem('register_cart_count', String(count)); } catch (err) { /* storage blocked */ }
        if (previous !== null) {
            previous = parseInt(previous, 10);
            if (count > previous) {
                var name = $.trim($('#cart_contents tr:first td:nth-child(3)').text().split('[')[0]).replace(/\s+/g, ' ');
                announce(<?= json_encode(lang('Sales.register_item_added')) ?>.replace('{0}', name).replace('{1}', count));
            } else if (count < previous) {
                announce(<?= json_encode(lang('Sales.register_item_removed')) ?>.replace('{0}', count));
            }
        }

        // Sale channel: adding a payment reloads the page, so the choice is kept for the sale
        var $channels = $('input[name="sale_channel"]');
        if ($channels.length) {
            var savedChannel = null;
            try { savedChannel = sessionStorage.getItem('register_sale_channel'); } catch (err) { /* storage blocked */ }
            if (savedChannel) {
                $channels.filter('[value="' + savedChannel + '"]').prop('checked', true);
            }
            $channels.on('change', function() {
                try { sessionStorage.setItem('register_sale_channel', this.value); } catch (err) { /* storage blocked */ }
            });
        }

        // Comment: folded until asked for (open already when the sale has one)
        $('#comment_toggle').on('click', function() {
            $(this).prop('hidden', true).attr('aria-expanded', 'true');
            $('#comment_box').prop('hidden', false);
            $('#comment').trigger('focus');
        });

        // Authorization dialogs: Enter confirms, Esc cancels; the primary action gets the focus
        $('#da_modal, #pa_modal').on('shown.bs.modal', function() {
            var prefix = this.id.slice(0, 2);
            $('#' + prefix + '_state_request').is(':visible') && $('#' + prefix + '_request_btn').trigger('focus');
        }).on('keydown', function(e) {
            var prefix = this.id.slice(0, 2);
            var requesting = $('#' + prefix + '_state_request').is(':visible');
            if (e.key === 'Escape') {
                e.preventDefault();
                $('#' + prefix + (requesting ? '_cancel_btn' : '_cancel_wait_btn')).trigger('click');
            } else if (e.key === 'Enter' && !$(e.target).is('button, a')) {
                e.preventDefault();
                var $primary = $('#' + prefix + (requesting ? '_request_btn' : '_apply_btn'));
                $primary.prop('disabled') || $primary.trigger('click');
            }
        });
    });

    // PIN Modal logic
    window.registerCashierName = <?= json_encode($current_cashier_name) ?>;
    $(document).ready(function() {
        var currentCashierId = <?= (int)$current_cashier_id ?>;

        function showPinModal() {
            $('#pin_input').val('');
            $('#pin_error').text('');
            $('#pin_modal').modal('show');
        }

        $('#pin_modal').on('shown.bs.modal', function() {
            $('#pin_input').trigger('focus');
        }).on('keydown', function(e) {
            if (e.key === 'Escape') { // same exit as the close button
                e.preventDefault();
                window.location.href = '<?= site_url('home') ?>';
            } else if (e.key === 'Enter' && !$(e.target).is('button, a')) {
                e.preventDefault();
                submitPin($('#pin_input').val());
            }
        });

        if (currentCashierId <= 0) {
            showPinModal();
        }

        // Auto-submit when 4 digits are entered
        $('#pin_input').on('input', function() {
            var val = $(this).val().replace(/\D/g, '');
            $(this).val(val);
            if (val.length === 4) {
                submitPin(val);
            }
        });

        $('#pin_submit_btn').on('click', function() {
            submitPin($('#pin_input').val());
        });

        function submitPin(pin) {
            if (!pin || pin.length !== 4) {
                showPinError('<?= lang('Sales.pin_invalid') ?>');
                return;
            }

            $('#pin_submit_btn').prop('disabled', true);

            $.ajax({
                url: '<?= site_url('sales/verifyPin') ?>',
                type: 'POST',
                data: { pin: pin },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the cashier label (plain text: the name comes from the server)
                        $('#current_cashier_badge')
                            .empty()
                            .append('<span class="bi bi-person" aria-hidden="true"></span>')
                            .append(document.createTextNode(' <?= esc(lang('Sales.cashier'), 'js') ?>: ' + response.name));
                        window.registerCashierName = response.name;
                        window.registerAnnounce && window.registerAnnounce('<?= esc(lang('Sales.cashier'), 'js') ?>: ' + response.name);
                        $('#pin_modal').modal('hide');
                    } else {
                        showPinError(response.message);
                    }
                },
                error: function() {
                    showPinError('<?= esc(lang('Sales.pin_incorrect'), 'js') ?>');
                },
                complete: function() {
                    $('#pin_submit_btn').prop('disabled', false);
                }
            });
        }

        function showPinError(msg) {
            $('#pin_error').text(msg);
            $('#pin_input').val('').trigger('focus');
            // Shake animation
            $('#pin_input').addClass('is-invalid');
            setTimeout(function() { $('#pin_input').removeClass('is-invalid'); }, 600);
        }

        // Stock consultation button
        $('#pin_stock_consult_btn').on('click', function() {
            window.location.href = '<?= esc(site_url('sales/stockConsult')) ?>';
        });
    });
</script>

<?= view('partial/footer') ?>
