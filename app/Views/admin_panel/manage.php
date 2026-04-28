<?php
/**
 * @var array $branches
 * @var array $employees
 * @var array $access  [person_id][location_id] = bool
 * @var string $employee_table_headers
 */

use App\Models\Employee;

$emp_model = model(Employee::class);
$logged_in_id = $emp_model->get_logged_in_employee_info()->person_id;
?>

<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar print_hide" style="display:none;">
    <button class="btn btn-primary btn-sm pull-right modal-dlg"
            data-btn-new="<?= lang('Common.new') ?>"
            data-btn-submit="<?= lang('Common.submit') ?>"
            data-href="employees/view"
            title="<?= lang('Employees.new') ?>">
        <span class="glyphicon glyphicon-plus"></span>&nbsp;<?= lang('Employees.new') ?>
    </button>
</div>

<div id="toolbar" style="display:none;">
    <div class="pull-left form-inline" role="toolbar">
        <button id="delete" class="btn btn-default btn-sm print_hide" disabled>
            <span class="glyphicon glyphicon-trash"></span>&nbsp;<?= lang('Common.delete') ?>
        </button>
    </div>
</div>

<h4 style="margin: 12px 0 8px;">Panel de Administración</h4>

<ul class="nav nav-tabs" id="admin_tabs">
    <li class="active"><a href="#tab_branches"  data-toggle="tab">Sucursales</a></li>
    <li>              <a href="#tab_employees"  data-toggle="tab">Empleados</a></li>
    <li>              <a href="#tab_access"     data-toggle="tab">Accesos</a></li>
</ul>

<div class="tab-content" style="padding-top:16px;">

    <!-- ========================== SUCURSALES ========================== -->
    <div class="tab-pane active" id="tab_branches">
        <div class="row">
            <div class="col-xs-12 col-sm-6">
                <table class="table table-condensed table-striped">
                    <thead>
                        <tr><th>Sucursal</th><th style="width:80px;"></th></tr>
                    </thead>
                    <tbody id="branch_list">
                        <?php foreach ($branches as $branch): ?>
                        <tr id="branch_row_<?= $branch['location_id'] ?>">
                            <td><?= esc($branch['location_name']) ?></td>
                            <td>
                                <button class="btn btn-danger btn-xs btn-delete-branch"
                                        data-id="<?= $branch['location_id'] ?>"
                                        data-name="<?= esc($branch['location_name']) ?>">
                                    <span class="glyphicon glyphicon-trash"></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-xs-12 col-sm-5">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Nueva Sucursal</strong></div>
                    <div class="panel-body">
                        <div class="form-group form-group-sm">
                            <input type="text" id="new_branch_name" class="form-control"
                                   placeholder="Nombre de la sucursal">
                        </div>
                        <button id="btn_create_branch" class="btn btn-primary btn-sm">
                            <span class="glyphicon glyphicon-plus"></span> Crear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== EMPLEADOS ========================== -->
    <div class="tab-pane" id="tab_employees">
        <div id="emp_title_bar" class="btn-toolbar" style="margin-bottom:8px;">
            <button class="btn btn-primary btn-sm pull-right modal-dlg"
                    data-btn-new="<?= lang('Common.new') ?>"
                    data-btn-submit="<?= lang('Common.submit') ?>"
                    data-href="employees/view"
                    title="<?= lang('Employees.new') ?>">
                <span class="glyphicon glyphicon-plus"></span>&nbsp;<?= lang('Employees.new') ?>
            </button>
            <div class="pull-left form-inline">
                <button id="emp_delete" class="btn btn-default btn-sm" disabled>
                    <span class="glyphicon glyphicon-trash"></span>&nbsp;<?= lang('Common.delete') ?>
                </button>
            </div>
        </div>
        <div id="emp_table_holder">
            <table id="emp_table"></table>
        </div>
    </div>

    <!-- ========================== ACCESOS ========================== -->
    <div class="tab-pane" id="tab_access">
        <?php if (empty($branches)): ?>
            <p class="text-muted">No hay sucursales configuradas.</p>
        <?php elseif (empty($employees)): ?>
            <p class="text-muted">No hay empleados registrados.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-condensed table-bordered table-hover" id="access_matrix">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <?php foreach ($branches as $branch): ?>
                            <th class="text-center" style="min-width:90px;">
                                <?= esc($branch['location_name']) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><?= esc($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                        <?php foreach ($branches as $branch): ?>
                            <td class="text-center">
                                <input type="checkbox"
                                       class="access-toggle"
                                       data-person="<?= $emp['person_id'] ?>"
                                       data-location="<?= $branch['location_id'] ?>"
                                       <?= ($access[$emp['person_id']][$branch['location_id']] ?? false) ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted" style="font-size:12px;">
            Marcar otorga acceso del empleado a esa sucursal en todos los módulos. Desmarcar lo revoca.
        </p>
        <?php endif; ?>
    </div>

</div>

<script type="text/javascript">
$(document).ready(function() {
    <?= view('partial/bootstrap_tables_locale', ['controller_name' => 'employees']) ?>

    var empTableReady = false;

    // ---- Tab switching ----
    $('#admin_tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('href');
        if (target === '#tab_employees' && !empTableReady) {
            initEmpTable();
        }
    });

    // ---- EMPLEADOS bootstrap-table ----
    function initEmpTable() {
        empTableReady = true;

        var headers = <?= $employee_table_headers ?>;

        $('#emp_table')
            .addClass('table-striped table-bordered')
            .bootstrapTable({
                columns:          headers,
                url:              '<?= site_url('employees/search') ?>',
                sidePagination:   'server',
                pageSize:         <?= config(\Config\OSPOS::class)->settings['lines_per_page'] ?>,
                pagination:       true,
                search:           true,
                showColumns:      true,
                clickToSelect:    true,
                selectItemName:   'btSelectItem',
                uniqueId:         'people.person_id',
                trimOnSearch:     false,
                queryParamsType:  'limit',
                iconSize:         'sm',
                silentSort:       true,
                paginationVAlign: 'bottom',
                escape:           true,
                onCheck:          toggleEmpActions,
                onUncheck:        toggleEmpActions,
                onCheckAll:       toggleEmpActions,
                onUncheckAll:     toggleEmpActions,
                onLoadSuccess:    function() {
                    dialog_support.init('a.modal-dlg');
                    toggleEmpActions();
                }
            });

        dialog_support.init('button.modal-dlg');

        $('#emp_delete').on('click', function() {
            var selections = $('#emp_table').bootstrapTable('getSelections');
            var ids = $.map(selections, function(row) { return row['people.person_id']; });
            if (!ids.length) return;
            if (!confirm($.fn.bootstrapTable.defaults.formatConfirmAction('delete'))) return;
            $.post('employees/delete', {'ids[]': ids}, function(res) {
                if (res.success) {
                    $.notify(res.message, {type: 'success'});
                    $('#emp_table').bootstrapTable('refresh');
                } else {
                    $.notify(res.message, {type: 'danger'});
                }
            }, 'json');
        });
    }

    function toggleEmpActions() {
        var empty = $('#emp_table').bootstrapTable('getSelections').length === 0;
        $('#emp_delete').prop('disabled', empty);
    }

    // Override table_support.handle_submit to refresh emp_table when in employees tab
    var _orig_handle_submit = table_support.handle_submit;
    table_support.handle_submit = function(resource, response) {
        if (resource === 'employees' && empTableReady) {
            if (!response.success) {
                $.notify(response.message, {type: 'danger'});
            } else {
                $.notify(response.message, {type: 'success'});
                dialog_support.hide();
                $('#emp_table').bootstrapTable('refresh');
            }
            return false;
        }
        return _orig_handle_submit.call(this, resource, response);
    };

    // ---- SUCURSALES ----
    $('#btn_create_branch').on('click', function() {
        var name = $('#new_branch_name').val().trim();
        if (!name) { $.notify({message: 'Ingresá el nombre.'}, {type: 'warning'}); return; }
        $.post('<?= site_url('admin_panel/createBranch') ?>', {branch_name: name}, function(res) {
            $.notify({message: res.message}, {type: res.success ? 'success' : 'danger'});
            if (res.success) { $('#new_branch_name').val(''); setTimeout(function() { location.reload(); }, 700); }
        }, 'json');
    });

    $('#new_branch_name').on('keypress', function(e) { if (e.which === 13) $('#btn_create_branch').click(); });

    $(document).on('click', '.btn-delete-branch', function() {
        var id = $(this).data('id'), name = $(this).data('name');
        BootstrapDialog.confirm({
            title: 'Eliminar Sucursal',
            message: '¿Eliminar <strong>' + $('<span>').text(name).html() + '</strong>?',
            type: BootstrapDialog.TYPE_DANGER,
            btnOKLabel: 'Eliminar', btnOKClass: 'btn-danger',
            callback: function(ok) {
                if (!ok) return;
                $.post('<?= site_url('admin_panel/deleteBranch') ?>', {location_id: id}, function(res) {
                    $.notify({message: res.message}, {type: res.success ? 'success' : 'danger'});
                    if (res.success) setTimeout(function() { location.reload(); }, 700);
                }, 'json');
            }
        });
    });

    // ---- ACCESOS ----
    $(document).on('change', '.access-toggle', function() {
        var $cb = $(this);
        var grant = $cb.prop('checked') ? '1' : '0';
        $cb.prop('disabled', true);
        $.ajax({
            url: '<?= site_url('admin_panel/toggleAccess') ?>',
            type: 'POST',
            data: {person_id: $cb.data('person'), location_id: $cb.data('location'), grant: grant},
            dataType: 'json',
            success: function(res) {
                if (!res.success) {
                    $cb.prop('checked', !$cb.prop('checked'));
                    $.notify({message: res.message || 'Error.'}, {type: 'danger'});
                }
            },
            error: function() {
                $cb.prop('checked', !$cb.prop('checked'));
                $.notify({message: 'Error de conexión.'}, {type: 'danger'});
            },
            complete: function() { $cb.prop('disabled', false); }
        });
    });

});
</script>

<?= view('partial/footer') ?>
