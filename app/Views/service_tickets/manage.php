<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $config
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
            uniqueId: 'service_tickets.ticket_id',
        });
    });
</script>

<div id="title_bar" class="btn-toolbar print_hide">
    <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-new="<?= lang('Common.new') ?>" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang('Service_tickets.new') ?>">
        <span class="glyphicon glyphicon-wrench">&nbsp;</span><?= lang('Service_tickets.new') ?>
    </button>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <button id="delete" class="btn btn-default btn-sm print_hide">
            <span class="glyphicon glyphicon-trash">&nbsp;</span><?= lang('Common.delete') ?>
        </button>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
