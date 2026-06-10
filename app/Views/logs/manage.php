<?php
/**
 * @var array $employees
 * @var array $stock_locations
 * @var array $config
 */
?>
<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <h4 class="pull-left" style="margin: 4px 0 0;">
        <span class="glyphicon glyphicon-list-alt"></span>&nbsp;<?= lang('Logs.title') ?>
    </h4>
</div>

<div id="toolbar" class="form-inline print_hide" style="margin-bottom: 10px; flex-wrap: wrap; gap: 6px;">
    <div class="form-group">
        <label class="sr-only"><?= lang('Logs.date_from') ?></label>
        <div class="input-group input-group-sm">
            <span class="input-group-addon"><?= lang('Logs.date_from') ?></span>
            <input type="date" id="filter_date_from" class="form-control" value="">
        </div>
    </div>
    <div class="form-group">
        <label class="sr-only"><?= lang('Logs.date_to') ?></label>
        <div class="input-group input-group-sm">
            <span class="input-group-addon"><?= lang('Logs.date_to') ?></span>
            <input type="date" id="filter_date_to" class="form-control" value="">
        </div>
    </div>
    <select id="filter_type" class="form-control input-sm" style="min-width:140px;">
        <option value=""><?= lang('Logs.filter_all_types') ?></option>
        <option value="inventory"><?= lang('Logs.type_inventory') ?></option>
        <option value="sale"><?= lang('Logs.type_sale') ?></option>
        <option value="login"><?= lang('Logs.type_login') ?></option>
        <option value="logout"><?= lang('Logs.type_logout') ?></option>
        <option value="ticket_status"><?= lang('Logs.type_ticket_status') ?></option>
    </select>
    <select id="filter_employee" class="form-control input-sm" style="min-width:160px;">
        <option value="0"><?= lang('Logs.filter_all_employees') ?></option>
        <?php foreach ($employees as $emp): ?>
            <option value="<?= esc($emp['person_id']) ?>"><?= esc($emp['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filter_location" class="form-control input-sm" style="min-width:150px;">
        <option value="0"><?= lang('Logs.filter_all_locations') ?></option>
        <?php foreach ($stock_locations as $loc_id => $loc_name): ?>
            <option value="<?= esc($loc_id) ?>"><?= esc($loc_name) ?></option>
        <?php endforeach; ?>
    </select>
    <button id="btn_apply_filters" class="btn btn-primary btn-sm">
        <span class="glyphicon glyphicon-search"></span>&nbsp;<?= lang('Common.search') ?>
    </button>
</div>

<div id="table_holder">
    <table class="table table-condensed table-bordered table-hover" id="logs_table">
        <thead>
            <tr>
                <th style="width:150px;"><?= lang('Logs.col_date') ?></th>
                <th style="width:110px;"><?= lang('Logs.col_type') ?></th>
                <th style="width:140px;"><?= lang('Logs.col_employee') ?></th>
                <th style="width:120px;"><?= lang('Logs.col_location') ?></th>
                <th><?= lang('Logs.col_description') ?></th>
                <th style="width:80px;"><?= lang('Logs.col_reference') ?></th>
            </tr>
        </thead>
        <tbody id="logs_tbody">
            <tr><td colspan="6" class="text-center text-muted">&mdash;</td></tr>
        </tbody>
    </table>
    <div id="logs_pagination" class="text-center" style="margin-top:10px;"></div>
    <div id="logs_total" class="text-right text-muted small" style="margin-top:4px;"></div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var currentPage = 0;
    var pageSize = 50;

    var TYPE_LABELS = {
        'inventory':     '<?= lang('Logs.type_inventory') ?>',
        'sale':          '<?= lang('Logs.type_sale') ?>',
        'login':         '<?= lang('Logs.type_login') ?>',
        'logout':        '<?= lang('Logs.type_logout') ?>',
        'ticket_status': '<?= lang('Logs.type_ticket_status') ?>'
    };

    var TYPE_CLASSES = {
        'inventory':     'info',
        'sale':          'success',
        'login':         'default',
        'logout':        'default',
        'ticket_status': 'warning'
    };

    function typeLabel(type) {
        var label = TYPE_LABELS[type] || type;
        var cls   = TYPE_CLASSES[type] || 'default';
        return '<span class="label label-' + cls + '">' + label + '</span>';
    }

    function loadLogs(page) {
        page = page || 0;
        currentPage = page;

        var params = {
            date_from:   $('#filter_date_from').val(),
            date_to:     $('#filter_date_to').val(),
            log_type:    $('#filter_type').val(),
            employee_id: $('#filter_employee').val(),
            location_id: $('#filter_location').val(),
            limit:       pageSize,
            offset:      page * pageSize
        };

        $('#logs_tbody').html('<tr><td colspan="6" class="text-center"><span class="glyphicon glyphicon-refresh"></span></td></tr>');

        $.getJSON('<?= esc(site_url('logs/search')) ?>', params, function(data) {
            var tbody = $('#logs_tbody');
            tbody.empty();

            if (!data.rows || data.rows.length === 0) {
                tbody.append('<tr><td colspan="6" class="text-center text-muted"><?= lang('Items.no_items_to_display') ?></td></tr>');
                $('#logs_pagination').empty();
                $('#logs_total').empty();
                return;
            }

            $.each(data.rows, function(i, row) {
                var ref = row.reference_id ? '<small>#' + row.reference_id + '</small>' : '';
                var ip  = row.ip_address ? '<br><small class="text-muted">' + row.ip_address + '</small>' : '';
                tbody.append(
                    '<tr>' +
                    '<td><small>' + row.log_date + '</small></td>' +
                    '<td>' + typeLabel(row.log_type) + '</td>' +
                    '<td>' + (row.employee_name || '') + '</td>' +
                    '<td>' + (row.location_name || '') + '</td>' +
                    '<td>' + row.description + ip + '</td>' +
                    '<td>' + ref + '</td>' +
                    '</tr>'
                );
            });

            // Pagination
            var totalPages = Math.ceil(data.total / pageSize);
            var pagination = '';
            if (totalPages > 1) {
                pagination = '<ul class="pagination pagination-sm">';
                if (currentPage > 0) {
                    pagination += '<li><a href="#" data-page="' + (currentPage - 1) + '">&laquo;</a></li>';
                }
                var start = Math.max(0, currentPage - 2);
                var end   = Math.min(totalPages - 1, currentPage + 2);
                for (var p = start; p <= end; p++) {
                    pagination += '<li class="' + (p === currentPage ? 'active' : '') + '"><a href="#" data-page="' + p + '">' + (p + 1) + '</a></li>';
                }
                if (currentPage < totalPages - 1) {
                    pagination += '<li><a href="#" data-page="' + (currentPage + 1) + '">&raquo;</a></li>';
                }
                pagination += '</ul>';
            }
            $('#logs_pagination').html(pagination);
            $('#logs_total').text(data.total + ' registros');
        });
    }

    $('#btn_apply_filters').on('click', function() {
        loadLogs(0);
    });

    $(document).on('click', '#logs_pagination a', function(e) {
        e.preventDefault();
        loadLogs(parseInt($(this).data('page')));
    });

    // Auto-load on page load
    loadLogs(0);
});
</script>

<?= view('partial/footer') ?>
