<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $config
 * @var array $stock_locations
 * @var bool $show_location_filter
 */
?>

<?= view('partial/header') ?>

<style>
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(2, minmax(140px, 200px)) minmax(240px, 1fr);
        gap: 12px;
        margin: 12px 0;
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

    #toolbar .range-shortcut {
        border-color: #6c757d;
        color: #343a40;
    }

    #toolbar .range-shortcut[aria-pressed="true"] {
        background: #182735;
        border-color: #182735;
        color: #fff;
    }

    #toolbar .btn:focus-visible,
    #table th[tabindex]:focus-visible,
    #table .expense-edit:focus-visible,
    #title_bar .btn:focus-visible {
        outline: 3px solid #0d6efd;
        outline-offset: 2px;
        box-shadow: none;
    }

    #table th[tabindex] {
        cursor: pointer;
    }

    .expense-edit {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 32px;
        padding: 0 10px;
        border: 1px solid var(--bs-border-color, #ced4da);
        color: #343a40;
        white-space: nowrap;
    }

    .expense-edit .bi {
        font-size: 16px;
    }

    /* Actions stay visible at the right edge when the table scrolls sideways */
    #table th.col-actions,
    #table td.col-actions {
        position: sticky;
        right: 0;
        z-index: 2;
        text-align: center;
        background: var(--bs-body-bg, #fff);
        box-shadow: -1px 0 0 var(--bs-border-color, #dee2e6);
    }

    #delete_wrap {
        display: inline-block;
    }

    @media print {
        #toolbar,
        .col-actions {
            display: none !important;
        }
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        var MSG = {
            deleteCount: <?= json_encode(lang('Expenses.delete_count')) ?>,
            deleteHint: <?= json_encode(lang('Expenses.delete_hint')) ?>,
            deleteOne: <?= json_encode(lang('Expenses.delete_one')) ?>,
            deleteMany: <?= json_encode(lang('Expenses.delete_many')) ?>,
            and: <?= json_encode(lang('Expenses.and')) ?>,
            selectExpense: <?= json_encode(lang('Expenses.select_expense')) ?>,
            selectAll: <?= json_encode(lang('Expenses.select_all')) ?>,
            showing: <?= json_encode(lang('Expenses.showing')) ?>,
            actions: <?= json_encode(lang('Expenses.actions')) ?>,
            none: <?= json_encode(lang('Expenses.summary_none')) ?>
        };

        // Load the preset datarange picker (default range: today, unchanged)
        <?= view('partial/daterangepicker') ?>

        // Date shortcuts + full range in the field
        <?= view('partial/range_shortcuts') ?>

        <?= view('partial/bootstrap_tables_locale') ?>

        // Headers: keep every column; the edit column gets the "Actions" title and sticks to the right edge
        var headers = <?= $table_headers ?>;
        $.each(headers, function(i, header) {
            if (header.field === 'edit') {
                header.title = MSG.actions;
                header.class = 'col-actions print_hide';
                header.switchable = false;
            }
        });

        var set_summary = function(summary) {
            $('#sum_count').text(summary.count);
            $('#sum_total').text(summary.total);
            var $list = $('#sum_payments').empty();
            if (!summary.payments.length) {
                $('<li class="summary-empty"></li>').text(MSG.none).appendTo($list);
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
                $(this).find('input[name="btSelectItem"]').attr('aria-label', MSG.selectExpense.replace('{0}', $(this).data('uniqueid')));
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

        var delete_prompt = function(ids) {
            ids = $.map(ids.slice().sort(function(x, y) { return x - y; }), function(id) { return String(id); });
            var amount_of = function(id) {
                var row = $('#table').bootstrapTable('getRowByUniqueId', id);
                return row ? row.amount : '';
            };
            if (ids.length === 1) {
                return MSG.deleteOne.replace('{0}', ids[0]).replace('{1}', amount_of(ids[0]));
            }
            var items = $.map(ids, function(id) { return id + ' (' + amount_of(id) + ')'; });
            return MSG.deleteMany.replace('{0}', items.slice(0, -1).join(', ') + ' ' + MSG.and + ' ' + items[items.length - 1]);
        };

        table_support.init({
            resource: '<?= esc($controller_name) ?>',
            headers: headers,
            pageSize: <?= $config['lines_per_page'] ?>,
            uniqueId: 'expense_id',
            highlightHold: 1800,
            confirmMessage: function(action, ids) {
                return delete_prompt(ids);
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
                $('#expenses_showing').text(MSG.showing.replace('{0}', has_rows ? response.rows.length - 1 : 0).replace('{1}', response.total));
                if ($("#table tbody tr").length > 1) {
                    $("#table tbody tr:last td:first").html("");
                    $("#table tbody tr:last").css('font-weight', 'bold');
                }
                decorate_table();
            },
            queryParams: function() {
                return $.extend(arguments[0], {
                    "start_date": start_date,
                    "end_date": end_date,
                    "filters": [],
                    "location_id": $("#location_id_filter").val() || 'all'
                });
            }
        });

        $("#location_id_filter").on('change', function() {
            table_support.refresh();
        });
    });
</script>

<?= view('partial/print_receipt', ['print_after_sale' => false, 'selected_printer' => 'takings_printer']) ?>

<div id="title_bar" class="print_hide btn-toolbar">
    <button class="btn btn-info btn-sm float-end modal-dlg modal-dlg-expenses" data-btn-submit="<?= esc(lang('Expenses.save'), 'attr') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang(ucfirst($controller_name) . '.new') ?>">
        <span class="bi bi-tags" aria-hidden="true"></span> <?= lang(ucfirst($controller_name) . '.new') ?>
    </button>
</div>

<section id="expenses_summary" class="summary-cards" aria-live="polite" aria-atomic="true" aria-label="<?= esc(lang('Module.expenses'), 'attr') ?>">
    <div class="summary-card">
        <span class="summary-label"><?= lang('Expenses.summary_count') ?></span>
        <span class="summary-value" id="sum_count">–</span>
    </div>
    <div class="summary-card">
        <span class="summary-label"><?= lang('Expenses.summary_total') ?></span>
        <span class="summary-value" id="sum_total">–</span>
    </div>
    <div class="summary-card">
        <span class="summary-label"><?= lang('Expenses.summary_payments') ?></span>
        <ul class="summary-payments" id="sum_payments"></ul>
    </div>
</section>

<div id="expenses_showing" class="visually-hidden" role="status" aria-live="polite"></div>

<div id="toolbar">
    <div class="float-start d-flex flex-wrap gap-3 align-items-end" role="group">
        <span id="delete_wrap" class="print_hide" title="<?= esc(lang('Expenses.delete_hint'), 'attr') ?>">
            <button id="delete" class="btn btn-outline-danger btn-sm" aria-label="<?= esc(str_replace('{0}', '0', lang('Expenses.delete_count')), 'attr') ?>">
                <span class="bi bi-trash" aria-hidden="true"></span> <?= lang('Common.delete') ?> (<span id="delete_count">0</span>)
            </button>
        </span>

        <div class="filter-group">
            <label for="daterangepicker"><?= lang('Expenses.filter_date') ?></label>
            <div class="filter-row">
                <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control form-control-sm', 'id' => 'daterangepicker']) ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="<?= esc(lang('Expenses.shortcuts'), 'attr') ?>">
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="today" data-always-enabled aria-pressed="true"><?= lang('Expenses.today') ?></button>
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="yesterday" data-always-enabled aria-pressed="false"><?= lang('Expenses.yesterday') ?></button>
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="week" data-always-enabled aria-pressed="false"><?= lang('Expenses.this_week') ?></button>
                    <button type="button" class="btn btn-outline-secondary range-shortcut" data-range="month" data-always-enabled aria-pressed="false"><?= lang('Expenses.this_month') ?></button>
                </div>
            </div>
        </div>

        <?php if (!empty($show_location_filter) && !empty($stock_locations)): ?>
            <div class="filter-group">
                <label for="location_id_filter"><?= lang('Expenses.filter_location') ?></label>
                <?= form_dropdown('location_id_filter', ['all' => lang('Reports.all')] + $stock_locations, 'all', ['id' => 'location_id_filter', 'class' => 'form-select form-select-sm']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
