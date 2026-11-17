<?php
/**
 * @var array $categories
 * @var array $locations         location_id => name (branches the user may see)
 * @var int   $selected_location
 * @var int   $sale_location      branch the register sells from
 */
?>
<?= view('partial/header') ?>

<style>
    #stock_consult .sc-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    #stock_consult .sc-header h4 { margin: 0; font-size: 1.15rem; }
    #stock_consult .sc-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 12px; }
    #stock_consult .sc-toolbar .sc-search { width: 280px; max-width: 100%; }
    #stock_consult .table-wrap { max-height: calc(100vh - 235px); overflow: auto; border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 0; background: #fff; }
    #stock_consult table { margin: 0; }
    #stock_consult thead th { position: sticky; top: 0; z-index: 1; background: #fff; color: var(--bs-body-color); font-weight: 700; border-bottom: 2px solid var(--bs-border-color, #dee2e6); white-space: nowrap; cursor: pointer; user-select: none; }
    #stock_consult thead th.no-sort { cursor: default; }
    #stock_consult tbody tr:nth-child(odd) > td { background: #f2f2f2; }
    #stock_consult th, #stock_consult td { border: 1px solid var(--bs-border-color, #dee2e6); }
    #stock_consult td, #stock_consult th { padding-left: .6rem; padding-right: .6rem; }
    #stock_consult tbody tr:hover > td { background: #e6eaee; }
    #stock_consult tbody td:first-child, #stock_consult thead th:first-child { padding-left: .75rem; }
    #stock_consult .sc-photo { width: 52px; text-align: center; }
    #stock_consult .sc-photo .item-photo-btn { margin: 0 auto; }
    #stock_consult .sc-price-main { font-size: 15px; font-weight: 600; }
    #stock_consult thead th .sc-arrow { opacity: .4; font-size: .75em; margin-left: 4px; }
    #stock_consult thead th.sorted .sc-arrow { opacity: 1; }
    #stock_consult td.num, #stock_consult th.num { text-align: right; }
    #stock_consult .sc-footer { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px; margin-top: 10px; }
    #stock_consult tbody td { padding-top: .6rem; padding-bottom: .6rem; font-size: 14px; }
    #stock_consult .qty { font-weight: 700; font-size: 15px; }
    #stock_consult .qty.low { color: var(--bs-warning-text-emphasis, #664d03); }
    #stock_consult .qty.out { color: var(--bs-danger, #dc3545); }
    #stock_consult .sc-price-alt { color: var(--bs-body-color); }
    #stock_consult .sc-msg { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1080; max-width: 90vw; }
    #stock_consult .sc-loading { opacity: .5; transition: opacity .15s; }
</style>

<div id="stock_consult">
    <div class="sc-header print_hide">
        <a href="<?= site_url('sales') ?>" class="btn btn-outline-secondary btn-sm">
            <span class="bi bi-arrow-left"></span>&nbsp;<?= lang('Sales.pin_back') ?>
        </a>
        <h4><span class="bi bi-list-ul"></span>&nbsp;<?= lang('Sales.stock_consult_title') ?></h4>
    </div>

    <?php if (empty($locations)): ?>
        <div class="alert alert-warning"><?= lang('Sales.stock_consult_no_branch') ?></div>
    <?php else: ?>

    <div class="sc-toolbar print_hide">
        <input type="search" id="stock_search" class="form-control form-control-sm sc-search"
               placeholder="<?= lang('Sales.stock_consult_search') ?>" autocomplete="off" autofocus>
        <?php if (count($locations) > 1): ?>
            <select id="stock_location" class="form-select form-select-sm w-auto" title="<?= lang('Sales.stock_consult_branch') ?>">
                <?php foreach ($locations as $id => $name): ?>
                    <option value="<?= (int)$id ?>" <?= (int)$id === (int)$selected_location ? 'selected' : '' ?>><?= esc($name) ?></option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <input type="hidden" id="stock_location" value="<?= (int)$selected_location ?>">
            <span class="badge text-bg-secondary"><span class="bi bi-shop"></span>&nbsp;<?= esc($locations[$selected_location] ?? '') ?></span>
        <?php endif; ?>

        <?= form_dropdown('stock_category', $categories, 'all', ['id' => 'stock_category', 'class' => 'form-select form-select-sm w-auto']) ?>

        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="stock_include_zero">
            <label class="form-check-label small" for="stock_include_zero"><?= lang('Sales.stock_consult_include_zero') ?></label>
        </div>
    </div>

    <div class="table-wrap">
        <table id="stock_table" class="table table-sm align-middle">
            <thead>
                <tr>
                    <th class="no-sort sc-photo"></th>
                    <th data-sort="name"><?= lang('Items.name') ?><span class="sc-arrow bi bi-arrow-down-up"></span></th>
                    <th data-sort="item_number"><?= lang('Items.item_number') ?><span class="sc-arrow bi bi-arrow-down-up"></span></th>
                    <th data-sort="category"><?= lang('Items.category') ?><span class="sc-arrow bi bi-arrow-down-up"></span></th>
                    <th class="num" data-sort="unit_price"><?= lang('Items.unit_price') ?><span class="sc-arrow bi bi-arrow-down-up"></span></th>
                    <th class="num" data-sort="price_wholesale"><?= lang('Items.price_wholesale') ?><span class="sc-arrow bi bi-arrow-down-up"></span></th>
                    <th class="num" data-sort="price_reseller"><?= lang('Items.price_reseller') ?><span class="sc-arrow bi bi-arrow-down-up"></span></th>
                    <th class="num" data-sort="quantity" style="width:90px;"><?= lang('Items.quantity') ?><span class="sc-arrow bi bi-arrow-down-up"></span></th>
                    <th class="no-sort" style="width:110px;"></th>
                </tr>
            </thead>
            <tbody id="stock_tbody">
                <tr><td colspan="9" class="text-center text-muted py-4">&mdash;</td></tr>
            </tbody>
        </table>
    </div>

    <div class="sc-footer print_hide">
        <div class="d-flex align-items-center gap-2">
            <span id="stock_summary" class="small text-muted"></span>
            <select id="stock_page_size" class="form-select form-select-sm w-auto" title="<?= lang('Sales.stock_consult_per_page') ?>">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <nav aria-label="pagination"><ul id="stock_pagination" class="pagination pagination-sm mb-0"></ul></nav>
    </div>

    <?php endif; ?>
</div>

<script type="text/javascript">
$(document).ready(function() {
    if (!$('#stock_location').length) { return; }

    var state = { page: 0, size: 25, sort: 'name', dir: 'asc' };
    var searchTimer = null;
    var requestId = 0;
    var summaryTpl = <?= json_encode(lang('Sales.stock_consult_showing')) ?>;
    var outLabel = <?= json_encode(lang('Sales.stock_consult_out')) ?>;
    var saleLocation = <?= (int)$sale_location ?>;
    var addLabel = <?= json_encode(lang('Sales.stock_consult_add')) ?>;
    var otherBranchLabel = <?= json_encode(lang('Sales.stock_consult_add_other_branch')) ?>;
    var emptyLabel = <?= json_encode(lang('Items.no_items_to_display')) ?>;

    function qtyCell(q, text) {
        if (q < 1) { return '<span class="qty out">' + outLabel + '</span>'; }
        return '<span class="qty' + (q < 2 ? ' low' : '') + '">' + text + '</span>';
    }

    function addButton(r) {
        var sameBranch = parseInt($('#stock_location').val(), 10) === saleLocation;
        if (r.quantity < 1) { return ''; }
        if (!sameBranch) {
            return '<button type="button" class="btn btn-outline-secondary btn-sm" disabled title="' + otherBranchLabel + '">' + addLabel + '</button>';
        }
        return '<button type="button" class="btn btn-primary btn-sm sc-add" data-item="' + r.item_id + '"><span class="bi bi-cart-plus"></span> ' + addLabel + '</button>';
    }

    function flash(kind, text) {
        $('.sc-msg').remove();
        var el = $('<div class="sc-msg alert alert-' + kind + ' shadow-sm py-2 px-3" role="status"></div>').text(text).appendTo('#stock_consult');
        setTimeout(function() { el.fadeOut(200, function() { el.remove(); }); }, 2500);
    }

    function renderPagination(total) {
        var pages = Math.ceil(total / state.size);
        var ul = $('#stock_pagination').empty();
        if (pages <= 1) { return; }

        function item(label, page, opts) {
            opts = opts || {};
            var cls = 'page-item' + (opts.active ? ' active' : '') + (opts.disabled ? ' disabled' : '');
            return '<li class="' + cls + '"><a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>';
        }

        var html = item('&laquo;', state.page - 1, { disabled: state.page === 0 });
        var start = Math.max(0, state.page - 2), end = Math.min(pages - 1, state.page + 2);
        if (start > 0) {
            html += item('1', 0);
            if (start > 1) { html += item('&hellip;', start - 1); }
        }
        for (var p = start; p <= end; p++) { html += item(p + 1, p, { active: p === state.page }); }
        if (end < pages - 1) {
            if (end < pages - 2) { html += item('&hellip;', end + 1); }
            html += item(pages, pages - 1);
        }
        html += item('&raquo;', state.page + 1, { disabled: state.page >= pages - 1 });
        ul.html(html);
    }

    function updateSortHeaders() {
        $('#stock_table thead th[data-sort]').each(function() {
            var th = $(this), active = th.data('sort') === state.sort;
            th.toggleClass('sorted', active);
            th.find('.sc-arrow').attr('class', 'sc-arrow bi ' + (active ? (state.dir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up'));
        });
    }

    function load() {
        var myRequest = ++requestId;
        var params = {
            search: $('#stock_search').val(),
            category: $('#stock_category').val(),
            location_id: $('#stock_location').val(),
            include_zero: $('#stock_include_zero').is(':checked') ? 1 : 0,
            sort: state.sort,
            dir: state.dir,
            limit: state.size,
            offset: state.page * state.size
        };

        $('#stock_tbody').addClass('sc-loading');

        $.getJSON('<?= esc(site_url('sales/stockItems')) ?>', params, function(data) {
            if (myRequest !== requestId) { return; } // stale response
            var tbody = $('#stock_tbody').removeClass('sc-loading').empty();

            if (!data.rows || !data.rows.length) {
                tbody.append('<tr><td colspan="9" class="text-center text-muted py-4">' + emptyLabel + '</td></tr>');
                $('#stock_summary').text('');
                $('#stock_pagination').empty();
                return;
            }

            $.each(data.rows, function(i, r) {
                tbody.append(
                    '<tr' + (r.quantity < 1 ? ' class="text-muted"' : '') + '>' +
                    '<td class="sc-photo">' + r.photo + '</td>' +
                    '<td>' + r.name + '</td>' +
                    '<td>' + (r.item_number ? '<code>' + r.item_number + '</code>' : '') + '</td>' +
                    '<td>' + r.category + '</td>' +
                    '<td class="num"><span class="sc-price-main">' + r.unit_price + '</span></td>' +
                    '<td class="num sc-price-alt">' + r.price_wholesale + '</td>' +
                    '<td class="num sc-price-alt">' + r.price_reseller + '</td>' +
                    '<td class="num">' + qtyCell(r.quantity, r.quantity_text) + '</td>' +
                    '<td class="num">' + addButton(r) + '</td>' +
                    '</tr>'
                );
            });

            var from = state.page * state.size + 1;
            var to = Math.min(data.total, from + data.rows.length - 1);
            $('#stock_summary').text(summaryTpl.replace('%s', from).replace('%s', to).replace('%s', data.total));
            renderPagination(data.total);
        }).fail(function(jqXHR) {
            $('#stock_tbody').removeClass('sc-loading').html(
                '<tr><td colspan="9" class="text-danger small">Error ' + jqXHR.status + '</td></tr>'
            );
        });
    }

    function reload() { state.page = 0; load(); }

    $('#stock_search').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reload, 300);
    });
    $('#stock_category, #stock_location, #stock_include_zero').on('change', reload);
    $('#stock_page_size').on('change', function() { state.size = parseInt($(this).val(), 10); reload(); });

    $('#stock_table thead').on('click', 'th[data-sort]', function() {
        var col = $(this).data('sort');
        state.dir = (state.sort === col && state.dir === 'asc') ? 'desc' : 'asc';
        state.sort = col;
        updateSortHeaders();
        reload();
    });

    $('#stock_pagination').on('click', 'a.page-link', function(e) {
        e.preventDefault();
        if ($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) { return; }
        state.page = parseInt($(this).data('page'), 10);
        load();
    });

    // Add to sale: POST to the register, surface its error if any, else go to the register.
    $('#stock_tbody').on('click', '.sc-add', function() {
        var btn = $(this).prop('disabled', true);
        $.post('<?= esc(site_url('sales/add')) ?>', { item: btn.data('item') }, function(html) {
            var err = $('<div>').html(html).find('.alert-danger').first().text().trim();
            if (err) {
                btn.prop('disabled', false);
                flash('danger', err);
                return;
            }
            window.location.href = '<?= esc(site_url('sales')) ?>';
        }).fail(function() {
            btn.prop('disabled', false);
            flash('danger', 'Error');
        });
    });

    // "/" focuses search from anywhere (unless already typing in a field)
    $(document).on('keydown', function(e) {
        if (e.key === '/' && !$(e.target).is('input, select, textarea')) {
            e.preventDefault();
            $('#stock_search').trigger('focus').trigger('select');
        }
    });
    $('#stock_search').on('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); this.select(); } // scanner terminator: keep text selected for next scan
    });

    updateSortHeaders();
    load();
});
</script>

<?= view('partial/photo_modal') ?>

<?= view('partial/footer') ?>
