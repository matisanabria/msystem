<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $config
 * @var array $stock_locations
 * @var bool $show_location_filter
 */

use App\Models\Employee;
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    $(document).ready(function() {
        <?php
        echo view('partial/bootstrap_tables_locale');
        $employee = model(Employee::class);
        ?>

        table_support.init({
            employee_id: <?= $employee->get_logged_in_employee_info()->person_id ?>,
            resource: '<?= esc($controller_name) ?>',
            headers: <?= $table_headers ?>,
            pageSize: <?= $config['lines_per_page'] ?>,
            uniqueId: 'assistances.assistance_id',
            queryParams: function(params) {
                params.location_id = $('#location_id_filter').val() || 'all';
                return params;
            }
        });

        $('#location_id_filter').on('change', function() {
            table_support.refresh();
        });
    });
</script>

<div id="title_bar" class="btn-toolbar print_hide">
    <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-new="<?= lang('Common.new') ?>" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang('Assistances.new') ?>">
        <span class="glyphicon glyphicon-send">&nbsp;</span><?= lang('Assistances.new') ?>
    </button>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <button id="delete" class="btn btn-default btn-sm print_hide">
            <span class="glyphicon glyphicon-trash">&nbsp;</span><?= lang('Common.delete') ?>
        </button>
        <?php if (!empty($show_location_filter) && !empty($stock_locations)): ?>
            <?= form_dropdown('location_id_filter', ['all' => lang('Reports.all')] + $stock_locations, 'all', ['id' => 'location_id_filter', 'class' => 'form-control input-sm']) ?>
        <?php endif; ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
