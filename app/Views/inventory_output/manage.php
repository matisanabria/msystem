<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $reasons
 * @var array $config
 * @var array $stock_locations
 * @var bool $show_location_filter
 */
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    $(document).ready(function() {
        <?= view('partial/daterangepicker') ?>

        $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
            table_support.refresh();
        });

        <?= view('partial/bootstrap_tables_locale') ?>

        table_support.init({
            resource: '<?= esc($controller_name) ?>',
            headers: <?= $table_headers ?>,
            pageSize: <?= $config['lines_per_page'] ?>,
            uniqueId: 'output_id',
            queryParams: function() {
                return $.extend(arguments[0], {
                    "start_date": start_date,
                    "end_date": end_date,
                    "reason": $("#reason_filter").val() || '',
                    "location_id": $("#location_id_filter").val() || 'all',
                    "deleted_filter": $("#deleted_filter").val() || 'active'
                });
            }
        });

        $("#location_id_filter, #reason_filter, #deleted_filter").on('change', function() {
            table_support.refresh();
        });
    });
</script>

<div id="title_bar" class="print_hide btn-toolbar">
    <button class="btn btn-info btn-sm float-end modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang('Inventory_outputs.new') ?>">
        <span class="bi bi-box-arrow-right">&nbsp;</span><?= lang('Inventory_outputs.new') ?>
    </button>
</div>

<div id="toolbar">
    <div class="float-start d-flex flex-wrap gap-2 align-items-center" role="toolbar">
        <button id="delete" class="btn btn-outline-secondary btn-sm print_hide">
            <span class="bi bi-trash">&nbsp;</span><?= lang('Common.delete') ?>
        </button>
        <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control form-control-sm', 'id' => 'daterangepicker']) ?>
        <?= form_dropdown('reason_filter', ['' => lang('Inventory_outputs.all_reasons')] + $reasons, '', ['id' => 'reason_filter', 'class' => 'form-select form-select-sm']) ?>
        <?= form_dropdown('deleted_filter', [
            'active'  => lang('Inventory_outputs.filter_active'),
            'deleted' => lang('Inventory_outputs.filter_deleted'),
            'all'     => lang('Inventory_outputs.filter_all'),
        ], 'active', ['id' => 'deleted_filter', 'class' => 'form-select form-select-sm']) ?>
        <?php if (!empty($show_location_filter) && !empty($stock_locations)): ?>
            <?= form_dropdown('location_id_filter', ['all' => lang('Reports.all')] + $stock_locations, 'all', ['id' => 'location_id_filter', 'class' => 'form-select form-select-sm']) ?>
        <?php endif; ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
