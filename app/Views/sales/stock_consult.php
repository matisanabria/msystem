<?php
/**
 * @var array $categories
 * @var array $allowed_location_ids
 */
?>
<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <a href="<?= site_url('sales') ?>" class="btn btn-default btn-sm">
        <span class="glyphicon glyphicon-arrow-left"></span>&nbsp;<?= lang('Sales.pin_back') ?>
    </a>
    <h4 class="pull-left" style="margin: 4px 15px 0;">
        <span class="glyphicon glyphicon-th-list"></span>&nbsp;<?= lang('Sales.stock_consult_title') ?>
    </h4>
</div>

<div id="toolbar" class="form-inline print_hide" style="margin-bottom: 10px;">
    <input type="text" id="stock_search" class="form-control input-sm"
           placeholder="<?= lang('Sales.stock_consult_search') ?>" style="width: 280px;">
    <?= form_dropdown('stock_category', $categories, 'all', ['id' => 'stock_category', 'class' => 'form-control input-sm', 'style' => 'min-width: 180px;']) ?>
</div>

<div id="table_holder">
    <table id="stock_table" class="table table-condensed table-bordered table-hover">
        <thead>
            <tr>
                <th><?= lang('Items.name') ?></th>
                <th><?= lang('Items.item_number') ?></th>
                <th><?= lang('Items.category') ?></th>
                <th><?= lang('Items.unit_price') ?></th>
                <th><?= lang('Items.date_added') ?></th>
                <th style="width:70px; text-align:right;"><?= lang('Items.quantity') ?></th>
            </tr>
        </thead>
        <tbody id="stock_tbody">
            <tr><td colspan="6" class="text-center text-muted">&mdash;</td></tr>
        </tbody>
    </table>
    <div id="stock_pagination" class="text-center" style="margin-top:10px;"></div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var currentPage = 0;
    var pageSize = 25;
    var searchTimer = null;

    function loadStock(page) {
        page = page || 0;
        currentPage = page;

        var params = {
            search:   $('#stock_search').val(),
            category: $('#stock_category').val(),
            limit:    pageSize,
            offset:   page * pageSize
        };

        $.getJSON('<?= esc(site_url('sales/stockItems')) ?>', params, function(data) {
            var tbody = $('#stock_tbody');
            tbody.empty();

            if (!data.rows || data.rows.length === 0) {
                tbody.append('<tr><td colspan="6" class="text-center text-muted"><?= lang('Items.no_items_to_display') ?></td></tr>');
                $('#stock_pagination').empty();
                return;
            }

            $.each(data.rows, function(i, row) {
                tbody.append(
                    '<tr>' +
                    '<td>' + row.name + '</td>' +
                    '<td><code>' + (row.item_number || '') + '</code></td>' +
                    '<td>' + row.category + '</td>' +
                    '<td><strong>' + row.unit_price + '</strong></td>' +
                    '<td>' + (row.item_add_date || '') + '</td>' +
                    '<td style="text-align:right;">' + row.quantity + '</td>' +
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
            $('#stock_pagination').html(pagination);
        }).fail(function(jqXHR) {
            $('#stock_tbody').html('<tr><td colspan="6" class="text-danger small">Error ' + jqXHR.status + ': <pre style="white-space:pre-wrap">' + $('<div>').text(jqXHR.responseText.substring(0, 500)).html() + '</pre></td></tr>');
        });
    }

    loadStock(0);

    $('#stock_search').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { loadStock(0); }, 300);
    });

    $('#stock_category').on('change', function() {
        loadStock(0);
    });

    $(document).on('click', '#stock_pagination a', function(e) {
        e.preventDefault();
        loadStock(parseInt($(this).data('page')));
    });
});
</script>

<?= view('partial/footer') ?>
