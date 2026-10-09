<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $selected_filters
 * @var array $payment_filter_options
 * @var array $config
 */
?>

<?= view('partial/header', ['crumb_sub' => ['icon' => 'receipt', 'label' => lang('Sales.manage_title')]]) ?>

<style>
    .manage-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px 16px;
        margin-bottom: 12px;
    }

    .manage-head h1 {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        font-size: 22px;
    }

    .manage-head p {
        margin: 2px 0 0;
        color: #6c757d;
    }

    .manage-head .manage-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .sales-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(140px, 200px)) minmax(240px, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }

    .summary-card {
        padding: 10px 14px;
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 8px;
        background: var(--bs-body-bg, #fff);
    }

    .summary-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: #6c757d;
    }

    .summary-value {
        display: block;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
    }

    .summary-payments {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 20px;
        margin: 4px 0 0;
        padding: 0;
        list-style: none;
        font-variant-numeric: tabular-nums;
    }

    .summary-payments .summary-empty {
        color: #6c757d;
    }

    #toolbar .filter-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    #toolbar .filter-group > label {
        margin: 0;
        font-size: 12px;
        font-weight: 600;
        color: #495057;
    }

    #toolbar .filter-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }

    #daterangepicker {
        min-width: 14ch;
    }

    #toolbar .btn-check:focus-visible + .btn,
    #toolbar .btn:focus-visible,
    #table th[tabindex]:focus-visible,
    #table .sale-action:focus-visible {
        outline: 3px solid #0d6efd;
        outline-offset: 2px;
        box-shadow: none;
    }

    .manage-head .btn-outline-secondary,
    #toolbar .range-shortcut {
        border-color: #6c757d;
        color: #343a40;
    }

    #toolbar .range-shortcut[aria-pressed="true"] {
        background: #182735;
        border-color: #182735;
        color: #fff;
    }

    #table th[tabindex] {
        cursor: pointer;
    }

    .sale-channel {
        font-weight: 600;
        color: #084298;
        background: #e7f1ff;
    }

    .sale-channel-delivery {
        color: #5c3b00;
        background: #fff3cd;
    }

    .sale-channel-shipping {
        color: #3d2c70;
        background: #ece8f8;
    }

    .sale-actions {
        display: flex;
        gap: 6px;
        justify-content: center;
    }

    .sale-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: 1px solid var(--bs-border-color, #ced4da);
        border-radius: 6px;
    }

    .sale-action .bi {
        font-size: 18px;
        line-height: 1;
    }

    /* Actions stay visible at the right edge when the table scrolls sideways */
    #table th.col-actions,
    #table td.col-actions {
        position: sticky;
        right: 0;
        z-index: 2;
        width: 96px;
        min-width: 96px;
        background: var(--bs-body-bg, #fff);
        box-shadow: -1px 0 0 var(--bs-border-color, #dee2e6);
    }

    #table th.col-actions {
        text-align: center;
    }

    #delete_wrap {
        display: inline-block;
    }

    @media print {
        .sale-actions,
        #toolbar,
        .col-actions {
            display: none !important;
        }
    }
</style>

<?= view('partial/print_receipt', ['print_after_sale' => false, 'selected_printer' => 'takings_printer']) ?>

<script type="text/javascript">
    $(document).ready(function() {
        var MSG = {
            deleteCount: <?= json_encode(lang('Sales.manage_delete_count')) ?>,
            deleteHint: <?= json_encode(lang('Sales.manage_delete_hint')) ?>,
            deleteOne: <?= json_encode(lang('Sales.manage_delete_one')) ?>,
            deleteMany: <?= json_encode(lang('Sales.manage_delete_many')) ?>,
            and: <?= json_encode(lang('Sales.manage_and')) ?>,
            selectSale: <?= json_encode(lang('Sales.manage_select_sale')) ?>,
            selectAll: <?= json_encode(lang('Sales.manage_select_all')) ?>,
            showing: <?= json_encode(lang('Sales.manage_showing')) ?>,
            actions: <?= json_encode(lang('Sales.manage_actions')) ?>,
            noPayments: <?= json_encode(lang('Sales.manage_summary_none')) ?>,
            title: <?= json_encode(lang('Sales.manage_title')) ?>
        };

        // When any filter is clicked and the dropdown window is closed
        $('#filters').on('hidden.bs.select', function(e) {
            table_support.refresh();
        });

        $('#payment_filter').on('hidden.bs.select', function(e) {
            table_support.refresh();
        });

        // Load the preset datarange picker
        <?= view('partial/daterangepicker') ?>

        var has_time = <?= empty($config['date_or_time_format']) ? 'false' : 'true' ?>;

        // Show the whole range in the field
        var fit_date_field = function() {
            var $field = $('#daterangepicker');
            $field.css('width', (String($field.val()).length + 3) + 'ch');
        };
        fit_date_field();

        // Date shortcuts (Today, Yesterday, This week, This month)
        var shortcut_range = function(name) {
            var today = moment();
            switch (name) {
                case 'yesterday':
                    return [moment().subtract(1, 'day').startOf('day'), moment().subtract(1, 'day').endOf('day')];
                case 'week':
                    return [moment().startOf('isoWeek'), today.endOf('day')];
                case 'month':
                    return [moment().startOf('month'), today.endOf('day')];
                default:
                    return [moment().startOf('day'), today.endOf('day')];
            }
        };

        var to_param = function(m) {
            return m.format(has_time ? 'YYYY-MM-DD HH:mm:ss' : 'YYYY-MM-DD');
        };

        var sync_shortcuts = function() {
            $('.range-shortcut').each(function() {
                var range = shortcut_range($(this).data('range'));
                $(this).attr('aria-pressed', to_param(range[0]) === start_date && to_param(range[1]) === end_date ? 'true' : 'false');
            });
        };

        $('.range-shortcut').on('click', function() {
            var picker = $('#daterangepicker').data('daterangepicker');
            var range = shortcut_range($(this).data('range'));
            picker.setStartDate(range[0]);
            picker.setEndDate(range[1]);
            start_date = to_param(range[0]);
            end_date = to_param(range[1]);
            fit_date_field();
            sync_shortcuts();
            table_support.refresh();
        });

        $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
            fit_date_field();
            sync_shortcuts();
            table_support.refresh();
        });
        sync_shortcuts();

        <?= view('partial/bootstrap_tables_locale') ?>

        table_support.query_params = function() {
            return {
                "start_date": start_date,
                "end_date": end_date,
                "filters": $("#filters").val(),
                "payment_filter": $("#payment_filter").val(),
                "location_id": $("#location_id_filter").val() || 'all'
            }
        };

        $("#location_id_filter").on('change', function() {
            table_support.refresh();
        });

        // Names for the selectpicker buttons (their <select> is hidden)
        $('#filters, #payment_filter').each(function() {
            $(this).closest('.bootstrap-select').children('button').attr('aria-label', $('label[for="' + this.id + '"]').text());
        });

        // Headers: keep every column and field; the edit cell carries the receipt and edit buttons under "Actions"
        var headers = <?= $table_headers ?>;
        $.each(headers, function(i, header) {
            if (header.field === 'edit') {
                header.title = MSG.actions;
                header.class = 'col-actions print_hide';
                header.switchable = false;
            }
        });

        var announce_showing = function(shown, total) {
            $('#sales_showing').text(MSG.showing.replace('{0}', shown).replace('{1}', total));
        };

        var set_summary = function(summary) {
            $('#sum_count').text(summary.count);
            $('#sum_total').text(summary.total);
            var $list = $('#sum_payments').empty();
            if (!summary.payments.length) {
                $('<li class="summary-empty"></li>').text(MSG.noPayments).appendTo($list);
            }
            $.each(summary.payments, function(i, payment) {
                $('<li></li>').text(payment.type + ' ' + payment.amount).appendTo($list);
            });
        };

        // Keyboard + aria-sort on sortable headers, labels on checkboxes
        var decorate_table = function() {
            var tbl = $('#table').data('bootstrap.table');
            var sort_name = tbl && tbl.options.sortName;
            var sort_order = tbl && tbl.options.sortOrder;
            $('#table thead th').each(function() {
                var $th = $(this);
                if ($th.find('.th-inner.sortable').length) {
                    $th.attr({tabindex: 0, 'aria-sort': $th.data('field') === sort_name ? (sort_order === 'desc' ? 'descending' : 'ascending') : 'none'});
                }
            });
            $('#table thead input[name="btSelectAll"]').attr('aria-label', MSG.selectAll);
            $('#table tbody tr[data-uniqueid]').each(function() {
                $(this).find('input[name="btSelectItem"]').attr('aria-label', MSG.selectSale.replace('{0}', $(this).data('uniqueid')));
            });
        };

        $('#table').on('post-header.bs.table sort.bs.table post-body.bs.table', function() {
            setTimeout(decorate_table, 0);
        });

        $('#table').on('keydown', 'thead th[tabindex]', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                var field = $(this).data('field');
                $(this).find('.th-inner').trigger('click');
                setTimeout(function() { $('#table thead th[data-field="' + field + '"]').trigger('focus'); }, 60);
            }
        });

        var id_list = function(ids) {
            ids = $.map(ids.slice().sort(function(x, y) { return x - y; }), function(id) { return String(id); });
            if (ids.length < 2) {
                return ids.join('');
            }
            return ids.slice(0, -1).join(', ') + ' ' + MSG.and + ' ' + ids[ids.length - 1];
        };

        table_support.init({
            resource: '<?= esc($controller_name) ?>',
            headers: headers,
            pageSize: <?= $config['lines_per_page'] ?>,
            uniqueId: 'sale_id',
            confirmMessage: function(action, ids) {
                return (ids.length === 1 ? MSG.deleteOne : MSG.deleteMany).replace('{0}', id_list(ids));
            },
            enableActions: function() {
                var count = table_support.selected_ids().length;
                $('#delete_count').text(count);
                $('#delete').attr('aria-label', MSG.deleteCount.replace('{0}', count));
                $('#delete_wrap').attr('title', count ? '' : MSG.deleteHint);
            },
            onLoadSuccess: function(response) {
                var has_rows = response.total > 0;
                set_summary(response.summary);
                announce_showing(has_rows ? response.rows.length - 1 : 0, response.total);
                if ($("#table tbody tr").length > 1) {
                    $("#table tbody tr:last td:first").html("");
                    $("#table tbody tr:last").css('font-weight', 'bold');
                }
                decorate_table();
            },
            queryParams: function() {
                return $.extend(arguments[0], table_support.query_params());
            }
        });

        // Print the list as filtered: add the period and active filters to the printed page
        $('#print_list_button').on('click', function() {
            var parts = [MSG.title + ': ' + $('#daterangepicker').val()];
            $('#toolbar .filter-group').each(function() {
                var $group = $(this);
                if ($group.find('#daterangepicker').length) {
                    return;
                }
                var text = $.trim($group.find('.bootstrap-select .filter-option-inner-inner, select:not(.selectpicker) option:selected').first().text());
                parts.push($.trim($group.children('label').text()) + ': ' + text);
            });
            $('#print_filters').text(parts.join(' · '));
            printdoc();
        });
    });
</script>

<div class="manage-head">
    <div>
        <h1><span class="bi bi-receipt" aria-hidden="true"></span> <?= lang('Sales.manage_title') ?></h1>
        <p><?= lang('Sales.manage_subtitle') ?></p>
    </div>
    <div class="manage-buttons print_hide">
        <?= anchor("sales", '<span class="bi bi-cart" aria-hidden="true"></span> ' . lang('Sales.manage_back_to_sell'), ['class' => 'btn btn-primary btn-sm', 'id' => 'show_sales_button']) ?>
        <button type="button" id="print_list_button" class="btn btn-outline-secondary btn-sm">
            <span class="bi bi-printer" aria-hidden="true"></span> <?= lang('Sales.manage_print_list') ?>
        </button>
    </div>
</div>

<p id="print_filters" class="d-none d-print-block"></p>

<section id="sales_summary" class="sales-summary" aria-live="polite" aria-atomic="true" aria-label="<?= esc(lang('Sales.manage_title'), 'attr') ?>">
    <div class="summary-card">
        <span class="summary-label"><?= lang('Sales.manage_summary_count') ?></span>
        <span class="summary-value" id="sum_count">–</span>
    </div>
    <div class="summary-card">
        <span class="summary-label"><?= lang('Sales.manage_summary_total') ?></span>
        <span class="summary-value" id="sum_total">–</span>
    </div>
    <div class="summary-card">
        <span class="summary-label"><?= lang('Sales.manage_summary_payments') ?></span>
        <ul class="summary-payments" id="sum_payments"></ul>
    </div>
</section>

<div id="sales_showing" class="visually-hidden" role="status" aria-live="polite"></div>

<div id="toolbar">
    <div class="float-start d-flex flex-wrap gap-3 align-items-end" role="group" aria-label="<?= esc(lang('Sales.manage_filters'), 'attr') ?>">
        <span id="delete_wrap" class="print_hide" title="<?= esc(lang('Sales.manage_delete_hint'), 'attr') ?>">
            <button id="delete" class="btn btn-outline-danger btn-sm" aria-label="<?= esc(str_replace('{0}', '0', lang('Sales.manage_delete_count')), 'attr') ?>">
                <span class="bi bi-trash" aria-hidden="true"></span> <?= lang('Common.delete') ?> (<span id="delete_count">0</span>)
            </button>
        </span>

        <div class="filter-group">
            <label for="daterangepicker"><?= lang('Sales.manage_filter_date') ?></label>
            <div class="filter-row">
                <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control form-control-sm', 'id' => 'daterangepicker']) ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="<?= esc(lang('Sales.manage_shortcuts'), 'attr') ?>">
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="today" data-always-enabled aria-pressed="true"><?= lang('Sales.manage_today') ?></button>
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="yesterday" data-always-enabled aria-pressed="false"><?= lang('Sales.manage_yesterday') ?></button>
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="week" data-always-enabled aria-pressed="false"><?= lang('Sales.manage_this_week') ?></button>
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="month" data-always-enabled aria-pressed="false"><?= lang('Sales.manage_this_month') ?></button>
                </div>
            </div>
        </div>

        <div class="filter-group">
            <label for="filters"><?= lang('Sales.manage_filter_channel') ?></label>
            <?= form_multiselect('filters[]', $filters, $selected_filters, [
                'id'                        => 'filters',
                'data-none-selected-text'   => lang('Sales.manage_all'),
                'class'                     => 'selectpicker show-menu-arrow',
                'data-selected-text-format' => 'count > 1',
                'data-style'                => 'btn-outline-secondary btn-sm',
                'data-width'                => 'fit'
            ]) ?>
        </div>

        <div class="filter-group">
            <label for="payment_filter"><?= lang('Sales.manage_filter_payment') ?></label>
            <?= form_dropdown('payment_filter', $payment_filter_options, '', [
                'id'          => 'payment_filter',
                'class'       => 'selectpicker show-menu-arrow',
                'data-style'  => 'btn-outline-secondary btn-sm',
                'data-width'  => 'fit'
            ]) ?>
        </div>

        <?php if (!empty($show_location_filter) && !empty($stock_locations)): ?>
            <div class="filter-group">
                <label for="location_id_filter"><?= lang('Sales.manage_filter_location') ?></label>
                <?= form_dropdown('location_id_filter', ['all' => lang('Reports.all')] + $stock_locations, 'all', ['id' => 'location_id_filter', 'class' => 'form-select form-select-sm']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
