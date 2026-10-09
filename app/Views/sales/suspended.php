<?php
/**
 * @var array $suspended_sales
 * @var bool $cart_has_items
 * @var array $config
 */

use App\Models\Employee;
use App\Models\Customer;

$this->dinner_table = model(Dinner_table::class);
$customer_model = model(Customer::class);    // TODO: Should we be accessing models in a view rather than passing this data to the view via the controller?
$employee_model = model(Employee::class);

$now = time();

/** Amber age label for sales suspended more than 24 hours ago, empty otherwise. */
$age_label = static function (int $timestamp) use ($now): string {
    $days = intdiv($now - $timestamp, 86400);
    if ($days < 1) {
        return '';
    }
    if ($days < 30) {
        return $days === 1 ? lang('Sales.suspended_ago_day') : str_replace('{0}', (string) $days, lang('Sales.suspended_ago_days'));
    }
    $months = intdiv($days, 30);

    return $months === 1 ? lang('Sales.suspended_ago_month') : str_replace('{0}', (string) $months, lang('Sales.suspended_ago_months'));
};
?>

<style>
    .suspended-dlg .modal-dialog {
        width: calc(100% - 32px);
        max-width: 900px;
    }

    .suspended-scroll {
        overflow-x: auto;
    }

    .suspended-table {
        margin-bottom: 0;
    }

    .suspended-table th,
    .suspended-table td {
        vertical-align: middle;
    }

    .suspended-table .col-nowrap {
        white-space: nowrap;
    }

    .suspended-table .col-comment {
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .suspended-table .text-none {
        color: #6c757d;
    }

    .suspended-age {
        display: inline-block;
        margin-top: 2px;
        padding: 1px 8px;
        border-radius: 10px;
        background: #fff3cd;
        color: #5c3b00;
        font-size: 12px;
        font-weight: 600;
    }

    /* Action column stays at the right edge when the table has to scroll */
    .suspended-table th:last-child,
    .suspended-table td:last-child {
        position: sticky;
        right: 0;
        background: var(--bs-body-bg, #fff);
        box-shadow: -1px 0 0 var(--bs-border-color, #dee2e6);
    }

    .suspended-resume {
        min-height: 40px;
        padding: 0 16px;
        white-space: nowrap;
    }

    .suspended-dlg .btn:focus-visible {
        outline: 3px solid #0d6efd;
        outline-offset: 2px;
        box-shadow: none;
    }

    .suspended-confirm {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 12px;
        margin-bottom: 12px;
        padding: 10px 12px;
        border: 1px solid #ffda6a;
        border-radius: 6px;
        background: #fff3cd;
        color: #5c3b00;
    }

    .suspended-confirm[hidden] {
        display: none;
    }

    .suspended-confirm .suspended-confirm-text {
        flex: 1 1 220px;
        font-weight: 600;
    }

    .suspended-empty {
        padding: 32px 16px;
        text-align: center;
        color: #6c757d;
    }

    .suspended-empty .bi {
        display: block;
        margin-bottom: 8px;
        font-size: 40px;
        opacity: 0.6;
    }
</style>

<div id="suspended_root" data-cart-has-items="<?= !empty($cart_has_items) ? '1' : '0' ?>">

    <div id="suspended_confirm" class="suspended-confirm" role="alert" hidden>
        <span class="suspended-confirm-text"><?= lang('Sales.suspended_resume_confirm') ?></span>
        <button type="button" class="btn btn-primary" id="suspended_confirm_yes"><?= lang('Sales.suspended_resume_anyway') ?></button>
        <button type="button" class="btn btn-outline-secondary" id="suspended_confirm_no"><?= lang('Sales.suspended_resume_keep') ?></button>
    </div>

    <?php if (empty($suspended_sales)) { ?>
        <div class="suspended-empty">
            <span class="bi bi-inbox" aria-hidden="true"></span>
            <?= lang('Sales.suspended_empty') ?>
        </div>
    <?php } else { ?>
        <div class="suspended-scroll">
            <table id="suspended_sales_table" class="table table-striped table-hover suspended-table">
                <thead>
                    <tr>
                        <th class="col-nowrap"><?= lang('Sales.suspended_doc_id') ?></th>
                        <th class="col-nowrap"><?= lang('Sales.suspended_date_time') ?></th>
                        <?php if ($config['dinner_table_enable']) { ?>
                            <th><?= lang('Sales.table') ?></th>
                        <?php } ?>
                        <th><?= lang('Sales.customer') ?></th>
                        <th class="col-nowrap"><?= lang('Sales.employee') ?></th>
                        <th><?= lang('Sales.comments') ?></th>
                        <th><span class="visually-hidden"><?= lang('Sales.unsuspend') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suspended_sales as $suspended_sale) {
                        $doc_id = ($suspended_sale['doc_id'] ?? '') !== '' ? $suspended_sale['doc_id'] : $suspended_sale['sale_id'];
                        $timestamp = strtotime($suspended_sale['sale_time']);

                        $customer_name = '';
                        if (isset($suspended_sale['customer_id'])) {
                            $customer_data = $customer_model->get_info($suspended_sale['customer_id']);
                            $customer_name = trim("$customer_data->first_name $customer_data->last_name");
                        }

                        $employee_name = '';
                        if (isset($suspended_sale['employee_id'])) {
                            $employee_data = $employee_model->get_info($suspended_sale['employee_id']);
                            $employee_name = trim("$employee_data->first_name $employee_data->last_name");
                        }

                        $age = $age_label($timestamp);
                        $comment = (string) ($suspended_sale['comment'] ?? '');
                    ?>
                        <tr>
                            <td class="col-nowrap"><?= esc($doc_id) ?></td>
                            <td class="col-nowrap">
                                <?= date('d/m/Y H:i', $timestamp) ?>
                                <?php if ($age !== '') { ?>
                                    <br><span class="suspended-age"><?= esc($age) ?></span>
                                <?php } ?>
                            </td>
                            <?php if ($config['dinner_table_enable']) { ?>
                                <td><?= esc($this->dinner_table->get_name($suspended_sale['dinner_table_id'])) ?></td>
                            <?php } ?>
                            <td>
                                <?php if ($customer_name !== '') { ?>
                                    <?= esc($customer_name) ?>
                                <?php } else { ?>
                                    <span class="text-none"><?= lang('Sales.suspended_no_customer') ?></span>
                                <?php } ?>
                            </td>
                            <td class="col-nowrap"><?= esc($employee_name) ?></td>
                            <td class="col-comment" title="<?= esc($comment, 'attr') ?>"><?= esc($comment) ?></td>
                            <td>
                                <?= form_open('sales/unsuspend', ['class' => 'suspended-resume-form']) ?>
                                <?= form_hidden('suspended_sale_id', (string) $suspended_sale['sale_id']) ?>
                                <button type="submit" class="btn btn-primary suspended-resume"
                                    aria-label="<?= esc(str_replace('{0}', (string) $doc_id, lang('Sales.suspended_resume_aria')), 'attr') ?>">
                                    <?= lang('Sales.suspended_resume') ?>
                                </button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</div>

<script>
    (function() {
        var $root = $('#suspended_root');
        var $confirm = $('#suspended_confirm');
        var pendingForm = null;
        var pendingButton = null;

        // Cart already has items: ask before the resume replaces it (the server always replaces it)
        $root.on('submit', '.suspended-resume-form', function(event) {
            if ($root.data('cart-has-items') !== 1) {
                return;
            }
            event.preventDefault();
            pendingForm = this;
            pendingButton = $(this).find('.suspended-resume')[0];
            $confirm.prop('hidden', false);
            $('#suspended_confirm_no').trigger('focus');
        });

        $('#suspended_confirm_yes').on('click', function() {
            if (pendingForm) {
                pendingForm.submit();
            }
        });

        $('#suspended_confirm_no').on('click', function() {
            $confirm.prop('hidden', true);
            pendingForm = null;
            if (pendingButton) {
                pendingButton.focus();
            }
        });
    })();
</script>
