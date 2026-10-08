<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $categories
 * @var array $stock_locations
 * @var int $stock_location
 * @var array $config
 */

use App\Models\Employee;
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#generate_barcodes').click(function() {
            window.open(
                'index.php/items/generateTickets/' + table_support.selected_ids().join(':'),
                '_blank'
            );
        });

        // When any filter is clicked and the dropdown window is closed
        $('#filters').on('hidden.bs.select', function(e) {
            table_support.refresh();
        });

        // Load the preset daterange picker
        <?= view('partial/daterangepicker') ?>
        // Set the beginning of time as starting date
        $('#daterangepicker').data('daterangepicker').setStartDate("<?= date($config['dateformat'], mktime(0, 0, 0, 01, 01, 2010)) ?>");
        // Update the hidden inputs with the selected dates before submitting the search data
        var start_date = "<?= date('Y-m-d', mktime(0, 0, 0, 01, 01, 2010)) ?>";
        $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
            table_support.refresh();
        });

        $("#stock_location").change(function() {
            table_support.refresh();
        });

        $("#category_filter").change(function() {
            table_support.refresh();
        });

        <?php
        echo view('partial/bootstrap_tables_locale');
        $employee = model(Employee::class);
        ?>

        table_support.init({
            employee_id: <?= $employee->get_logged_in_employee_info()->person_id ?>,
            resource: '<?= esc($controller_name) ?>',
            headers: <?= $table_headers ?>,
            pageSize: <?= $config['lines_per_page'] ?>,
            uniqueId: 'items.item_id',
            queryParams: function() {
                return $.extend(arguments[0], {
                    "start_date": start_date,
                    "end_date": end_date,
                    "stock_location": $("#stock_location").val(),
                    "filters": $("#filters").val(),
                    "category": $("#category_filter").val()
                });
            }
        });
    });

</script>

<?= view('partial/photo_modal') ?>

<div id="title_bar" class="btn-toolbar">
    <button class="btn btn-info btn-sm float-end modal-dlg" data-btn-new="<?= lang('Items.save_and_new') ?>" data-btn-submit="<?= lang('Items.save') ?>" data-hint-new="<?= esc(lang('Items.save_and_new_hint'), 'attr') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang(ucfirst($controller_name) . '.new') ?>">
        <span class="bi bi-tag">&nbsp;</span><?= lang(ucfirst($controller_name) . '.new') ?>
    </button>
</div>

<div id="toolbar">
    <div class="float-start d-flex flex-wrap gap-2 align-items-center" role="toolbar">
        <button id="delete" class="btn btn-outline-secondary btn-sm print_hide">
            <span class="bi bi-trash">&nbsp;</span><?= lang('Common.delete') ?>
        </button>
        <button id="generate_barcodes" class="btn btn-outline-secondary btn-sm print_hide" data-href="<?= "$controller_name/generateBarcodes" ?>" title="<?= lang('Items.generate_barcodes') ?>">
            <span class="bi bi-upc-scan">&nbsp;</span><?= lang('Items.generate_barcodes') ?>
        </button>
        <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control form-control-sm', 'id' => 'daterangepicker']) ?>
        <?= form_dropdown('category', $categories, 'all', [
            'id'               => 'category_filter',
            'class'            => 'selectpicker show-menu-arrow',
            'data-style'       => 'btn-outline-secondary btn-sm',
            'data-width'       => 'fit',
            'data-live-search' => 'true'
        ]) ?>
        <?= form_multiselect('filters[]', $filters, [''], [
            'id'                        => 'filters',
            'class'                     => 'selectpicker show-menu-arrow',
            'data-none-selected-text'   => lang('Common.none_selected_text'),
            'data-selected-text-format' => 'count > 1',
            'data-style'                => 'btn-outline-secondary btn-sm',
            'data-width'                => 'fit'
        ]) ?>
        <?php
        if (count($stock_locations) > 1) {
            echo form_dropdown(
                'stock_location',
                $stock_locations,
                $stock_location,
                [
                    'id'         => 'stock_location',
                    'class'      => 'selectpicker show-menu-arrow',
                    'data-style' => 'btn-outline-secondary btn-sm',
                    'data-width' => 'fit'
                ]
            );
        }
        ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
