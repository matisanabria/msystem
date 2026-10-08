<?php
/**
 * @var array $branches
 * @var string $employee_table_headers
 */

$js_strings = [
    'rename_title'          => lang('Admin_panel.rename_title'),
    'branch_name_label'     => lang('Admin_panel.branch_name_label'),
    'save'                  => lang('Admin_panel.save'),
    'cancel'                => lang('Admin_panel.cancel'),
    'delete'                => lang('Admin_panel.delete'),
    'delete_branch_title'   => lang('Admin_panel.delete_branch_title'),
    'name_required'         => lang('Admin_panel.name_required'),
    'error_connection'      => lang('Admin_panel.error_connection'),
    'employee_delete'       => lang('Admin_panel.employee_delete'),
    'employee_delete_title' => lang('Admin_panel.employee_delete_title'),
    'employee_delete_confirm' => lang('Admin_panel.employee_delete_confirm'),
    'last_backup'           => lang('Admin_panel.last_backup'),
    'ago_today'             => lang('Admin_panel.ago_today'),
    'ago_day'               => lang('Admin_panel.ago_day'),
    'ago_days'              => lang('Admin_panel.ago_days'),
    'last_backup_none'      => lang('Admin_panel.last_backup_none'),
    'backup_warn'           => lang('Admin_panel.backup_warn'),
    'backup_danger'         => lang('Admin_panel.backup_danger'),
    'backup_file'           => lang('Admin_panel.backup_file'),
    'backup_date'           => lang('Admin_panel.backup_date'),
    'backup_size'           => lang('Admin_panel.backup_size'),
    'actions'               => lang('Admin_panel.actions'),
    'backup_download'       => lang('Admin_panel.backup_download'),
    'backup_download_aria'  => lang('Admin_panel.backup_download_aria'),
    'backup_delete_aria'    => lang('Admin_panel.backup_delete_aria'),
    'backup_delete_title'   => lang('Admin_panel.backup_delete_title'),
    'backup_delete_confirm' => lang('Admin_panel.backup_delete_confirm'),
    'backup_delete_latest'  => lang('Admin_panel.backup_delete_latest'),
    'backup_creating'       => lang('Admin_panel.backup_creating'),
    'backup_created'        => lang('Backup.backup_created'),
    'backup_failed'         => lang('Backup.backup_failed'),
    'no_backups'            => lang('Backup.no_backups'),
    'delete_branch_confirm' => lang('Admin_panel.delete_branch_confirm'),
    'rename_aria'           => lang('Admin_panel.rename_aria'),
    'delete_aria'           => lang('Admin_panel.delete_aria'),
];
?>

<?= view('partial/header') ?>

<h1 class="admin-title"><span class="bi bi-gear" style="color:#18bc9c" aria-hidden="true"></span> <?= lang('Module.admin_panel') ?></h1>
<p class="admin-subtitle"><?= lang('Admin_panel.subtitle') ?></p>

<ul class="nav nav-tabs admin-tabs" id="admin_tabs" role="tablist" aria-label="<?= esc(lang('Admin_panel.tabs_label'), 'attr') ?>">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="admin-tab-branches" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab_branches" aria-controls="tab_branches" aria-selected="true"><?= lang('Admin_panel.tab_branches') ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="admin-tab-employees" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab_employees" aria-controls="tab_employees" aria-selected="false"><?= lang('Admin_panel.tab_employees') ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="admin-tab-backup" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab_backup" aria-controls="tab_backup" aria-selected="false"><?= lang('Admin_panel.tab_backup') ?></button>
    </li>
</ul>

<div class="tab-content" id="admin_tabs_content" style="padding-top:16px;">

    <!-- ========================== SUCURSALES ========================== -->
    <div class="tab-pane fade show active" id="tab_branches" role="tabpanel" aria-labelledby="admin-tab-branches" tabindex="0">
        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr><th scope="col"><?= lang('Admin_panel.branch') ?></th><th scope="col" style="width:290px;"><?= lang('Admin_panel.actions') ?></th></tr>
                    </thead>
                    <tbody id="branch_list">
                        <?php if (empty($branches)): ?>
                            <tr><td colspan="2" class="text-body-secondary"><?= lang('Admin_panel.no_branches') ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr id="branch_row_<?= $branch['location_id'] ?>">
                                <td><?= esc($branch['location_name']) ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <button type="button" class="btn-action-text btn-action-neutral btn-rename-branch"
                                                data-id="<?= $branch['location_id'] ?>" data-name="<?= esc($branch['location_name']) ?>"
                                                aria-label="<?= esc(lang('Admin_panel.rename_aria', [$branch['location_name']]), 'attr') ?>">
                                            <span class="bi bi-pencil-square" aria-hidden="true"></span> <?= lang('Admin_panel.rename') ?>
                                        </button>
                                        <button type="button" class="btn-action-text btn-action-danger btn-delete-branch"
                                                data-id="<?= $branch['location_id'] ?>" data-name="<?= esc($branch['location_name']) ?>"
                                                aria-label="<?= esc(lang('Admin_panel.delete_aria', [$branch['location_name']]), 'attr') ?>">
                                            <span class="bi bi-trash" aria-hidden="true"></span> <?= lang('Admin_panel.delete') ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-12 col-lg-5">
                <form id="new_branch_form" class="card" novalidate>
                    <div class="card-header"><strong><?= lang('Admin_panel.new_branch') ?></strong></div>
                    <div class="card-body">
                        <label class="form-label fw-semibold" for="new_branch_name"><?= lang('Admin_panel.branch_name_label') ?></label>
                        <input type="text" id="new_branch_name" class="form-control mb-3" autocomplete="off" style="min-height:40px;font-size:16px;" aria-describedby="branch_live">
                        <button type="submit" id="btn_create_branch" class="btn btn-primary" style="min-height:40px;">
                            <span class="bi bi-plus-lg" aria-hidden="true"></span> <?= lang('Admin_panel.create') ?>
                        </button>
                        <div class="admin-live" id="branch_live" role="status" aria-live="polite"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================== EMPLEADOS ========================== -->
    <div class="tab-pane fade" id="tab_employees" role="tabpanel" aria-labelledby="admin-tab-employees" tabindex="0">
        <div id="emp_title_bar" class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
            <button id="emp_delete" type="button" class="btn btn-outline-danger btn-sm" disabled>
                <span class="bi bi-trash" aria-hidden="true"></span> <span id="emp_delete_label"><?= lang('Admin_panel.employee_delete', [0]) ?></span>
            </button>
            <button type="button" class="btn btn-primary btn-sm modal-dlg modal-dlg-employee"
                    data-btn-submit="<?= esc(lang('Employees.save'), 'attr') ?>"
                    data-btn-cancel="<?= esc(lang('Employees.cancel'), 'attr') ?>"
                    data-href="employees/view"
                    title="<?= esc(lang('Employees.new'), 'attr') ?>">
                <span class="bi bi-plus-lg" aria-hidden="true"></span> <?= lang('Employees.new') ?>
            </button>
        </div>
        <div id="emp_table_holder">
            <table id="emp_table"></table>
        </div>
    </div>

    <!-- ========================== RESPALDO ========================== -->
    <div class="tab-pane fade" id="tab_backup" role="tabpanel" aria-labelledby="admin-tab-backup" tabindex="0">
        <div id="backup_status" class="backup-status" role="status"></div>
        <div class="mb-2">
            <button id="btn_create_backup" type="button" class="btn btn-primary" style="min-height:40px;">
                <span class="bi bi-download" aria-hidden="true"></span> <span class="btn-label"><?= lang('Backup.create_backup') ?></span>
            </button>
        </div>
        <div class="admin-live" id="backup_live" role="status" aria-live="polite"></div>
        <div id="backup_list_container">
            <p class="text-body-secondary"><?= lang('Backup.no_backups') ?></p>
        </div>
    </div>

</div>

<script type="text/javascript">
$(document).ready(function() {
    <?= view('partial/bootstrap_tables_locale', ['controller_name' => 'employees']) ?>

    var T = <?= json_encode($js_strings, JSON_UNESCAPED_UNICODE) ?>;
    var fmt = function(template) {
        var args = Array.prototype.slice.call(arguments, 1);
        return template.replace(/\{(\d+)\}/g, function(m, i) { return args[i] !== undefined ? args[i] : m; });
    };
    var esc_html = function(text) { return $('<span>').text(text).html(); };
    var announce = function(selector, message) {
        var $el = $(selector).text('');
        setTimeout(function() { $el.text(message); }, 60);
    };

    var empTableReady = false;
    var backupTabReady = false;

    // ---- Tab switching (BS5 tabs handle the arrow keys on a role=tablist once their instances exist)
    $('#admin_tabs [data-bs-toggle="tab"]').each(function() { bootstrap.Tab.getOrCreateInstance(this); });
    $('#admin_tabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('data-bs-target');
        if (target === '#tab_employees' && !empTableReady) {
            initEmpTable();
        }
        if (target === '#tab_backup' && !backupTabReady) {
            backupTabReady = true;
            loadBackupList();
        }
    });

    // ---- EMPLEADOS bootstrap-table
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
            var names = $.map(selections, function(row) { return '<li>' + esc_html(row.name) + '</li>'; }).join('');
            BootstrapDialog.confirm({
                title: T.employee_delete_title,
                message: '<p>' + esc_html(T.employee_delete_confirm) + '</p><ul>' + names + '</ul>',
                type: BootstrapDialog.TYPE_DANGER,
                btnOKLabel: T.delete, btnOKClass: 'btn-danger', btnCancelLabel: T.cancel,
                callback: function(ok) {
                    if (!ok) return;
                    $.post('employees/delete', {'ids[]': ids}, function(res) {
                        $.notify(res.message, {type: res.success ? 'success' : 'danger'});
                        if (res.success) { $('#emp_table').bootstrapTable('refresh'); toggleEmpActions(); }
                    }, 'json');
                }
            });
        });
    }

    function toggleEmpActions() {
        var count = $('#emp_table').bootstrapTable('getSelections').length;
        $('#emp_delete').prop('disabled', count === 0);
        $('#emp_delete_label').text(fmt(T.employee_delete, count));
    }

    // Employees dialog: refresh the table when it saves
    var _orig_handle_submit = table_support.handle_submit;
    table_support.handle_submit = function(resource, response) {
        if (resource === 'employees' && empTableReady) {
            if (!response.success) {
                $.notify(response.message, {type: 'danger'});
            } else {
                $.notify(response.message, {type: 'success'});
                $('#emp_table').bootstrapTable('refresh');
            }
            return false;
        }
        return _orig_handle_submit.call(this, resource, response);
    };

    // ---- SUCURSALES
    var reloadBranches = function(done) {
        $('#branch_list').load(location.pathname + ' #branch_list > *', done);
    };

    $('#new_branch_form').on('submit', function(e) {
        e.preventDefault();
        var name = $('#new_branch_name').val().trim();
        if (!name) {
            announce('#branch_live', T.name_required);
            $('#new_branch_name').trigger('focus');
            return;
        }
        $('#btn_create_branch').prop('disabled', true);
        $.post('<?= site_url('admin_panel/createBranch') ?>', {branch_name: name}, function(res) {
            announce('#branch_live', res.message);
            if (res.success) {
                $('#new_branch_name').val('');
                reloadBranches();
            }
        }, 'json').fail(function() {
            announce('#branch_live', T.error_connection);
        }).always(function() {
            $('#btn_create_branch').prop('disabled', false);
            $('#new_branch_name').trigger('focus');
        });
    });

    $(document).on('click', '.btn-rename-branch', function() {
        var $btn = $(this);
        var id = $btn.data('id'), name = $btn.data('name');
        var dialog = BootstrapDialog.show({
            title: T.rename_title,
            message: '<form id="rename_branch_form" novalidate><label class="form-label fw-semibold" for="rename_branch_name">' + esc_html(T.branch_name_label) + '</label>' +
                '<input type="text" id="rename_branch_name" class="form-control" autocomplete="off" style="min-height:40px;font-size:16px;">' +
                '<div class="field-error" id="rename_branch_error" role="alert" style="color:#b02a37;font-size:14px;"></div><button type="submit" hidden tabindex="-1" aria-hidden="true"></button></form>',
            onshown: function() { $('#rename_branch_name').val(name).trigger('focus').select(); },
            onhidden: function() { $btn.length && document.contains($btn[0]) && $btn.trigger('focus'); },
            buttons: [
                {id: 'rename_cancel', label: T.cancel, cssClass: 'btn-outline-secondary', action: function(d) { d.close(); }},
                {id: 'rename_save', label: T.save, cssClass: 'btn-primary', action: function() { $('#rename_branch_form').trigger('submit'); }}
            ]
        });
        $(document).off('submit.rename').on('submit.rename', '#rename_branch_form', function(e) {
            e.preventDefault();
            var new_name = $('#rename_branch_name').val().trim();
            if (!new_name) { $('#rename_branch_error').text(T.name_required); $('#rename_branch_name').trigger('focus'); return; }
            $.post('<?= site_url('admin_panel/renameBranch') ?>', {location_id: id, branch_name: new_name}, function(res) {
                if (!res.success) { $('#rename_branch_error').text(res.message); $('#rename_branch_name').trigger('focus'); return; }
                dialog.close();
                announce('#branch_live', res.message);
                $.notify({message: res.message}, {type: 'success'});
                reloadBranches(function() { $('#branch_row_' + id + ' .btn-rename-branch').trigger('focus'); });
            }, 'json').fail(function() { $('#rename_branch_error').text(T.error_connection); });
        });
    });

    $(document).on('click', '.btn-delete-branch', function() {
        var $btn = $(this);
        var id = $btn.data('id'), name = $btn.data('name');
        BootstrapDialog.confirm({
            title: T.delete_branch_title,
            message: esc_html(fmt(T.delete_branch_confirm, name)),
            type: BootstrapDialog.TYPE_DANGER,
            btnOKLabel: T.delete, btnOKClass: 'btn-danger', btnCancelLabel: T.cancel,
            callback: function(ok) {
                if (!ok) return;
                $.post('<?= site_url('admin_panel/deleteBranch') ?>', {location_id: id}, function(res) {
                    announce('#branch_live', res.message);
                    $.notify({message: res.message}, {type: res.success ? 'success' : 'danger'});
                    if (res.success) { reloadBranches(function() { $('#new_branch_name').trigger('focus'); }); }
                }, 'json');
            }
        });
    });

    // ---- RESPALDO
    var lastBackups = [];

    function agoText(days) {
        if (days < 1) return T.ago_today;
        return days === 1 ? T.ago_day : fmt(T.ago_days, days);
    }

    function renderBackupStatus(backups) {
        var $status = $('#backup_status').removeClass('is-warn is-danger');
        if (!backups.length) {
            $status.text(T.last_backup_none);
            return;
        }
        var newest = backups[0];
        var html = '<strong>' + esc_html(fmt(T.last_backup, newest.date_display, agoText(newest.age_days))) + '</strong>';
        if (newest.age_days > 30) {
            $status.addClass('is-danger');
            html += ' <span class="bi bi-exclamation-octagon-fill" aria-hidden="true"></span> ' + esc_html(T.backup_danger);
        } else if (newest.age_days > 7) {
            $status.addClass('is-warn');
            html += ' <span class="bi bi-exclamation-triangle-fill" aria-hidden="true"></span> ' + esc_html(T.backup_warn);
        }
        $status.html(html);
    }

    function renderBackupList(backups, highlight) {
        lastBackups = backups || [];
        renderBackupStatus(lastBackups);
        var $container = $('#backup_list_container');
        if (!lastBackups.length) {
            $container.html('<p class="text-body-secondary">' + esc_html(T.no_backups) + '</p>');
            return;
        }
        var rows = '';
        $.each(lastBackups, function(i, b) {
            var name = esc_html(b.filename);
            rows += '<tr' + (highlight && b.filename === highlight ? ' class="backup-new"' : '') + ' data-file="' + name + '">' +
                '<td>' + name + '</td>' +
                '<td>' + esc_html(b.date_display) + '</td>' +
                '<td>' + esc_html(b.size) + '</td>' +
                '<td><div class="admin-actions">' +
                    '<a href="<?= site_url('admin_panel/backupDownload') ?>/' + encodeURIComponent(b.filename) + '" class="btn-action-text btn-action-neutral" aria-label="' + esc_html(fmt(T.backup_download_aria, b.filename)) + '">' +
                        '<span class="bi bi-download" aria-hidden="true"></span> ' + esc_html(T.backup_download) +
                    '</a>' +
                    '<button type="button" class="btn-action-text btn-action-danger btn-delete-backup" data-filename="' + name + '" data-date="' + esc_html(b.date_display) + '" data-index="' + i + '" aria-label="' + esc_html(fmt(T.backup_delete_aria, b.filename)) + '">' +
                        '<span class="bi bi-trash" aria-hidden="true"></span> ' + esc_html(T.delete) +
                    '</button>' +
                '</div></td>' +
            '</tr>';
        });
        $container.html(
            '<table class="table table-sm align-middle" id="backup_table">' +
                '<thead><tr>' +
                    '<th scope="col">' + esc_html(T.backup_file) + '</th><th scope="col">' + esc_html(T.backup_date) + '</th><th scope="col">' + esc_html(T.backup_size) + '</th><th scope="col" style="width:260px;">' + esc_html(T.actions) + '</th>' +
                '</tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
            '</table>'
        );
        if (highlight) {
            setTimeout(function() { $('#backup_table tr.backup-new').removeClass('backup-new'); }, 5000);
        }
    }

    function loadBackupList() {
        $.getJSON('<?= site_url('admin_panel/backupList') ?>', function(res) {
            renderBackupList(res.backups || []);
        });
    }

    $('#btn_create_backup').on('click', function() {
        var $btn = $(this);
        var $label = $btn.find('.btn-label');
        var original = $label.text();
        $btn.prop('disabled', true).attr('aria-busy', 'true');
        $btn.find('.bi').removeClass('bi-download').addClass('spinner-border spinner-border-sm').css({border: ''});
        $label.text(T.backup_creating);
        announce('#backup_live', T.backup_creating);
        $.ajax({
            url: '<?= site_url('admin_panel/backupCreate') ?>',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    announce('#backup_live', res.message);
                    renderBackupList(res.backups || [], res.new_file);
                } else {
                    announce('#backup_live', res.message || T.backup_failed);
                }
            },
            error: function() {
                announce('#backup_live', T.error_connection);
            },
            complete: function() {
                $label.text(original);
                $btn.prop('disabled', false).removeAttr('aria-busy');
                $btn.find('.bi').removeClass('spinner-border spinner-border-sm').addClass('bi-download');
                $btn.trigger('focus');
            }
        });
    });

    $(document).on('click', '.btn-delete-backup', function() {
        var $btn = $(this);
        var filename = $btn.data('filename'), date = $btn.data('date');
        var is_latest = Number($btn.data('index')) === 0;
        var message = '<p>' + esc_html(fmt(T.backup_delete_confirm, filename, date)) + '</p>' +
            (is_latest ? '<p class="fw-semibold text-danger-emphasis mb-0"><span class="bi bi-exclamation-triangle-fill" aria-hidden="true"></span> ' + esc_html(T.backup_delete_latest) + '</p>' : '');
        BootstrapDialog.confirm({
            title: T.backup_delete_title,
            message: message,
            type: BootstrapDialog.TYPE_DANGER,
            btnOKLabel: T.delete, btnOKClass: 'btn-danger', btnCancelLabel: T.cancel,
            callback: function(ok) {
                if (!ok) return;
                $.ajax({
                    url: '<?= site_url('admin_panel/backupDelete') ?>',
                    type: 'POST',
                    data: {filename: filename},
                    dataType: 'json',
                    success: function(res) {
                        announce('#backup_live', res.message || T.backup_failed);
                        if (res.success) { renderBackupList(res.backups || []); $('#btn_create_backup').trigger('focus'); }
                    },
                    error: function() { announce('#backup_live', T.error_connection); }
                });
            }
        });
    });

});
</script>

<?= view('partial/footer') ?>
