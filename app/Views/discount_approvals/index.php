<?php
/**
 * Price change / discount requests waiting for an administrator.
 * Rows are drawn from JSON (discount_approvals/pendingRows, /history) and refreshed without reloading the page.
 *
 * @var array  $config
 * @var object $user_info
 */

$module_icons  = include APPPATH . 'Views/partial/module_icons.php';
$module_colors = include APPPATH . 'Views/partial/module_colors.php';
?>
<?= view('partial/header') ?>

<div class="approvals-page">

    <div class="page-head" style="--mc: <?= $module_colors['discount_approvals'] ?>">
        <h1><span class="page-head-icon bi bi-<?= $module_icons['discount_approvals'] ?>" aria-hidden="true"></span> <?= lang('Module.discount_approvals') ?></h1>
        <p><?= lang('Discount_approvals.subtitle') ?></p>
    </div>

    <div class="approvals-toolbar">
        <ul class="nav nav-tabs" id="approvals_tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab_pending" data-bs-toggle="tab" data-bs-target="#pane_pending" type="button" role="tab" aria-controls="pane_pending" aria-selected="true">
                    <?= lang('Discount_approvals.tab_pending') ?> <span id="pending_count" class="badge text-bg-secondary" hidden>0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab_history" data-bs-toggle="tab" data-bs-target="#pane_history" type="button" role="tab" aria-controls="pane_history" aria-selected="false">
                    <?= lang('Discount_approvals.tab_history') ?>
                </button>
            </li>
        </ul>

        <div class="approvals-sound">
            <input type="checkbox" id="sound_toggle">
            <label for="sound_toggle"><?= lang('Discount_approvals.sound') ?></label>
        </div>
    </div>

    <div id="approvals_live" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="pane_pending" role="tabpanel" aria-labelledby="tab_pending">
            <div id="no_pending" class="approvals-empty" hidden>
                <span class="bi bi-check2-circle" aria-hidden="true"></span> <?= lang('Discount_approvals.none_pending') ?>
            </div>

            <div class="table-responsive">
                <table class="table approvals-table" id="approvals_table" hidden>
                    <thead>
                        <tr>
                            <th scope="col"><?= lang('Discount_approvals.col_wait') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_cashier') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_location') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_item') ?></th>
                            <th scope="col" class="num"><?= lang('Discount_approvals.col_qty') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_change') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_difference') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_action') ?></th>
                        </tr>
                    </thead>
                    <tbody id="approvals_tbody"></tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="pane_history" role="tabpanel" aria-labelledby="tab_history">
            <div id="no_history" class="approvals-empty" hidden>
                <span class="bi bi-clock-history" aria-hidden="true"></span> <?= lang('Discount_approvals.none_history') ?>
            </div>
            <div class="table-responsive">
                <table class="table approvals-table" id="history_table" hidden>
                    <thead>
                        <tr>
                            <th scope="col"><?= lang('Discount_approvals.col_requested') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_cashier') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_item') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_change') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_resolved_by') ?></th>
                            <th scope="col"><?= lang('Discount_approvals.col_result') ?></th>
                        </tr>
                    </thead>
                    <tbody id="history_tbody"></tbody>
                </table>
            </div>
            <p class="approvals-note"><?= lang('Discount_approvals.history_note') ?></p>
        </div>
    </div>
</div>

<div id="approval_code_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="approval_code_title">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header auth-header">
                <h4 class="modal-title" id="approval_code_title"><span class="bi bi-check-circle" aria-hidden="true"></span> <?= lang('Discount_approvals.approved_title') ?></h4>
            </div>
            <div class="modal-body approval-code-body">
                <dl class="approval-code-who">
                    <dt><?= lang('Discount_approvals.col_cashier') ?></dt>
                    <dd id="code_cashier"></dd>
                    <dt><?= lang('Discount_approvals.col_item') ?></dt>
                    <dd id="code_item"></dd>
                    <dt><?= lang('Discount_approvals.col_change') ?></dt>
                    <dd id="code_change"></dd>
                </dl>
                <p class="approval-code-prompt"><?= lang('Discount_approvals.code_prompt') ?></p>
                <div id="modal_code" class="approval-code" role="img" aria-label=""></div>
                <p id="code_expiry_countdown" class="approval-code-expiry"></p>
                <p class="approval-code-note"><?= lang('Discount_approvals.code_note') ?></p>
            </div>
            <div class="modal-footer auth-footer">
                <button type="button" class="btn btn-primary btn-lg" id="code_done" data-bs-dismiss="modal"><?= lang('Discount_approvals.understood') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var T = <?= json_encode([
        'approve'       => lang('Discount_approvals.approve'),
        'reject'        => lang('Discount_approvals.reject'),
        'approveLabel'  => lang('Discount_approvals.approve_label'),
        'rejectLabel'   => lang('Discount_approvals.reject_label'),
        'expired'       => lang('Discount_approvals.expired'),
        'expiresIn'     => lang('Discount_approvals.expires_in'),
        'codeExpired'   => lang('Discount_approvals.code_expired'),
        'confirmReject' => lang('Discount_approvals.confirm_reject'),
        'errApprove'    => lang('Discount_approvals.error_approve'),
        'errConnection' => lang('Discount_approvals.error_connection'),
        'newRequest'    => lang('Discount_approvals.live_new'),
        'approvedLive'  => lang('Discount_approvals.live_approved'),
        'rejectedLive'  => lang('Discount_approvals.live_rejected'),
        'resApproved'   => lang('Discount_approvals.result_approved'),
        'resRejected'   => lang('Discount_approvals.result_rejected'),
        'resExpired'    => lang('Discount_approvals.result_expired'),
        'warn'          => lang('Discount_approvals.wait_warn'),
        'late'          => lang('Discount_approvals.wait_late'),
        'codeFor'       => lang('Discount_approvals.code_for'),
    ], JSON_UNESCAPED_UNICODE) ?>;

    var REFRESH_MS   = 5000;
    var WARN_AFTER   = 120;   // seconds: amber
    var LATE_AFTER   = 300;   // seconds: red
    var CODE_LIFE    = 600;   // seconds a code is usable; older pending requests are shown as expired
    var clockSkew    = 0;     // server time - browser time, so the wait does not depend on this PC's clock
    var firstLoad    = true;
    var codeTimer    = null;
    var lastFocusEl  = null;

    var $live = $('#approvals_live');
    function announce(message) {
        $live.text('');
        setTimeout(function () { $live.text(message); }, 60);
    }

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function attr(s) { return esc(s).replace(/"/g, '&quot;'); }
    function nowTs() { return Math.floor(Date.now() / 1000) + clockSkew; }

    function fmtWait(seconds) {
        seconds = Math.max(0, seconds);
        var m = Math.floor(seconds / 60), s = seconds % 60;
        return (m > 0 ? m + 'm ' : '') + s + 's';
    }

    // Column titles as data-label so the narrow (card) layout can show them next to each value
    function labelCells($table) {
        var labels = $table.find('thead th').map(function () { return $.trim($(this).text()); }).get();
        $table.find('tbody tr').each(function () {
            $(this).children('td').each(function (index) {
                if (!$(this).hasClass('cell-plain')) { $(this).attr('data-label', labels[index] || ''); }
            });
        });
    }

    // ---- pending list -------------------------------------------------------------------------
    function rowHtml(r) {
        var who  = r.cashier_name + ' / ' + r.item_name;
        var approve = r.expired ? '' :
            '<button type="button" class="btn btn-success approval-btn btn-approve" data-id="' + r.approval_id + '"' +
            ' aria-label="' + attr(T.approveLabel.replace('{0}', r.item_name).replace('{1}', r.cashier_name)) + '">' +
            '<span class="bi bi-check-lg" aria-hidden="true"></span> ' + esc(T.approve) + '</button>';
        var reject =
            '<button type="button" class="btn btn-outline-danger approval-btn btn-reject" data-id="' + r.approval_id + '"' +
            ' aria-label="' + attr(T.rejectLabel.replace('{0}', r.item_name).replace('{1}', r.cashier_name)) + '">' +
            '<span class="bi bi-x-lg" aria-hidden="true"></span> ' + esc(T.reject) + '</button>';

        return '<tr class="approval-row" data-id="' + r.approval_id + '" data-created="' + r.created_ts + '"' +
            ' data-cashier="' + attr(r.cashier_name) + '" data-item="' + attr(r.item_name) + '" data-change="' + attr(r.change_label + ' · ' + r.change_prices) + '">' +
            '<td class="wait-cell cell-plain"><span class="wait-time"></span><small class="wait-clock">' + esc(r.time_label) + '</small></td>' +
            '<td>' + esc(r.cashier_name) + '</td>' +
            '<td>' + esc(r.location_name) + '</td>' +
            '<td>' + esc(r.item_name) + '</td>' +
            '<td class="num">' + esc(r.qty_fmt) + '</td>' +
            '<td><strong>' + esc(r.change_label) + '</strong><small class="change-prices">' + esc(r.change_prices) + '</small></td>' +
            '<td class="diff diff-' + r.diff_class + '">' + esc(r.diff_text) + '</td>' +
            '<td class="cell-plain"><div class="actions">' + approve + reject + '</div></td>' +
            '</tr>';
    }

    // Wait cell: normal up to 2 min, amber up to 5, red after; icon + word besides colour
    function updateWaits() {
        var now = nowTs();
        $('#approvals_tbody .approval-row').each(function () {
            var elapsed = now - parseInt($(this).data('created'), 10);
            var cls = 'wait-ok', icon = 'hourglass-split', extra = '';
            if (elapsed > CODE_LIFE) {
                cls = 'wait-late'; icon = 'x-octagon'; extra = ' · ' + T.expired;
            } else if (elapsed > LATE_AFTER) {
                cls = 'wait-late'; icon = 'exclamation-octagon'; extra = ' · ' + T.late;
            } else if (elapsed > WARN_AFTER) {
                cls = 'wait-warn'; icon = 'exclamation-triangle'; extra = ' · ' + T.warn;
            }
            $(this).find('.wait-time')
                .attr('class', 'wait-time ' + cls)
                .html('<span class="bi bi-' + icon + '" aria-hidden="true"></span> ' + esc(fmtWait(elapsed) + extra));

            if (elapsed > CODE_LIFE) {
                $(this).find('.btn-approve').remove();   // expired: nothing to approve any more
            }
        });
    }

    function syncEmpty() {
        var n = $('#approvals_tbody .approval-row').length;
        $('#no_pending').prop('hidden', n > 0);
        $('#approvals_table').prop('hidden', n === 0);
        $('#pending_count').text(n).prop('hidden', n === 0);
        window.daSetBadge && window.daSetBadge(n);
    }

    function reconcile(rows) {
        var ids = rows.map(function (r) { return r.approval_id; });

        $('#approvals_tbody .approval-row').each(function () {
            if (ids.indexOf(parseInt($(this).data('id'), 10)) === -1) {
                $(this).remove();
            }
        });

        // oldest wait first: the server returns them oldest first, so new ones always go to the end;
        // rows already on screen are left untouched (keeps keyboard focus on their buttons)
        rows.forEach(function (r) {
            var $existing = $('#approvals_tbody .approval-row[data-id="' + r.approval_id + '"]');
            if ($existing.length) {
                if (r.expired && $existing.find('.btn-approve').length) {
                    $existing.find('.btn-approve').remove();
                }
                return;
            }
            $('#approvals_tbody').append(rowHtml(r));
            if (!firstLoad) {
                announce(T.newRequest.replace('{0}', r.item_name).replace('{1}', r.cashier_name));
            }
        });

        firstLoad = false;
        labelCells($('#approvals_table'));
        updateWaits();
        syncEmpty();
    }

    function refreshRows() {
        $.getJSON('<?= base_url('discount_approvals/pendingRows') ?>', function (res) {
            if (res.now) { clockSkew = res.now - Math.floor(Date.now() / 1000); }
            reconcile(res.rows || []);
        });
    }

    // ---- approve / reject ---------------------------------------------------------------------
    function showCode(code, $row) {
        var $code = $('#modal_code').empty().attr('aria-label', T.codeFor.replace('{0}', code.split('').join(' ')));
        code.split('').forEach(function (d) {
            $code.append($('<span class="approval-digit" aria-hidden="true"></span>').text(d));
        });
        $('#code_cashier').text($row.data('cashier'));
        $('#code_item').text($row.data('item'));
        $('#code_change').text($row.data('change'));

        var expiry = nowTs() + CODE_LIFE;
        if (codeTimer) clearInterval(codeTimer);
        var tick = function () {
            var remaining = Math.max(0, expiry - nowTs());
            $('#code_expiry_countdown').text(remaining === 0 ? T.codeExpired : T.expiresIn + ': ' + fmtWait(remaining));
            if (remaining === 0) clearInterval(codeTimer);
        };
        tick();
        codeTimer = setInterval(tick, 1000);
        $('#approval_code_modal').modal('show');
    }

    $(document).on('click', '.btn-approve', function () {
        var $btn = $(this), $row = $btn.closest('tr');
        var id = $btn.data('id');
        var label = $btn.html();
        lastFocusEl = $row.next('tr').find('.btn-approve, .btn-reject').first()[0] || $row.prev('tr').find('.btn-approve, .btn-reject').first()[0] || null;
        $btn.prop('disabled', true);

        $.ajax({
            url: '<?= base_url('discount_approvals/approve') ?>',
            type: 'POST',
            data: { approval_id: id },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showCode(res.code, $row);
                    announce(T.approvedLive.replace('{0}', $row.data('item')).replace('{1}', $row.data('cashier')));
                    $row.remove();
                    syncEmpty();
                } else {
                    $('#approvals_live').text(res.message || T.errApprove);
                    $btn.prop('disabled', false).html(label);
                    refreshRows();
                }
            },
            error: function () {
                announce(T.errConnection);
                $btn.prop('disabled', false).html(label);
            }
        });
    });

    $(document).on('click', '.btn-reject', function () {
        var $row = $(this).closest('tr');
        var id = $(this).data('id');
        if (!confirm(T.confirmReject)) return;
        lastFocusEl = $row.next('tr').find('.btn-approve, .btn-reject').first()[0] || $row.prev('tr').find('.btn-approve, .btn-reject').first()[0] || null;

        $.ajax({
            url: '<?= base_url('discount_approvals/reject') ?>',
            type: 'POST',
            data: { approval_id: id },
            dataType: 'json',
            success: function () {
                announce(T.rejectedLive.replace('{0}', $row.data('item')).replace('{1}', $row.data('cashier')));
                $row.remove();
                syncEmpty();
                lastFocusEl && $(lastFocusEl).trigger('focus');
            }
        });
    });

    // after the code dialog: focus goes to the next request, or the tab when the list is empty
    $('#approval_code_modal').on('shown.bs.modal', function () {
        $('#code_done').trigger('focus');
    }).on('hidden.bs.modal', function () {
        if (codeTimer) clearInterval(codeTimer);
        window.registerFocusReturn = null;
        if (lastFocusEl && document.contains(lastFocusEl)) {
            $(lastFocusEl).trigger('focus');
        } else {
            $('#tab_pending').trigger('focus');
        }
    });

    // ---- history ------------------------------------------------------------------------------
    function resultHtml(key) {
        var map = {
            approved: ['check-circle', T.resApproved, 'res-approved'],
            rejected: ['x-circle', T.resRejected, 'res-rejected'],
            expired:  ['clock-history', T.resExpired, 'res-expired']
        };
        var m = map[key] || map.expired;
        return '<span class="result ' + m[2] + '"><span class="bi bi-' + m[0] + '" aria-hidden="true"></span> ' + esc(m[1]) + '</span>';
    }

    function loadHistory() {
        $.getJSON('<?= base_url('discount_approvals/history') ?>', function (res) {
            var rows = res.rows || [];
            var html = rows.map(function (r) {
                return '<tr>' +
                    '<td>' + esc(r.date_label) + '</td>' +
                    '<td>' + esc(r.cashier_name) + '</td>' +
                    '<td>' + esc(r.item_name) + ' <small class="text-muted">× ' + esc(r.qty_fmt) + '</small></td>' +
                    '<td><strong>' + esc(r.change_label) + '</strong><small class="change-prices">' + esc(r.change_prices) + '</small></td>' +
                    '<td>' + esc(r.resolver) + '</td>' +
                    '<td>' + resultHtml(r.result) + '</td>' +
                    '</tr>';
            }).join('');
            $('#history_tbody').html(html);
            labelCells($('#history_table'));
            $('#history_table').prop('hidden', rows.length === 0);
            $('#no_history').prop('hidden', rows.length > 0);
        });
    }
    $('#tab_history').on('shown.bs.tab', loadHistory);

    // ---- sound (off by default, remembered in this browser) -------------------------------------
    try { $('#sound_toggle').prop('checked', localStorage.getItem('da_sound') === '1'); } catch (e) { /* storage blocked */ }
    $('#sound_toggle').on('change', function () {
        var on = $(this).is(':checked');
        try { localStorage.setItem('da_sound', on ? '1' : '0'); } catch (e) { /* storage blocked */ }
        if (on && window.daPlayChime) { window.daPlayChime(true); }   // a sample, and the click unlocks audio
    });

    // ---- start ----------------------------------------------------------------------------------
    refreshRows();
    setInterval(refreshRows, REFRESH_MS);
    setInterval(updateWaits, 1000);
})();
</script>

<?= view('partial/footer') ?>
