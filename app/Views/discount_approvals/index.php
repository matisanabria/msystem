<?php
/**
 * @var array  $pending
 * @var array  $config
 * @var object $user_info
 */
?>
<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <h4 class="pull-left" style="margin: 4px 0 0;">
        <span class="glyphicon glyphicon-tag"></span>&nbsp;<?= lang('Module.discount_approvals') ?>
    </h4>
    <span id="pending_badge" class="label label-danger" style="font-size:14px; margin-left:10px; display:none;"></span>
</div>

<?php if (empty($pending)): ?>
    <div id="no_pending" class="alert alert-info" style="margin-top:20px;">
        <span class="glyphicon glyphicon-ok"></span>&nbsp;No hay solicitudes de descuento pendientes.
    </div>
<?php else: ?>
    <div id="no_pending" class="alert alert-info" style="margin-top:20px; display:none;">
        <span class="glyphicon glyphicon-ok"></span>&nbsp;No hay solicitudes de descuento pendientes.
    </div>
<?php endif; ?>

<div id="approval_code_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" style="max-width:360px; width:360px;">
        <div class="modal-content">
            <div class="modal-header" style="background:#5cb85c; color:#fff;">
                <h4 class="modal-title"><span class="glyphicon glyphicon-ok-circle"></span>&nbsp;Descuento Aprobado</h4>
            </div>
            <div class="modal-body" style="text-align:center;">
                <p style="margin-bottom:12px; color:#555;">Comunique verbalmente este código al cajero:</p>
                <div id="modal_code" style="display:flex; justify-content:center; gap:10px; margin:10px 0 14px;"></div>
                <div id="code_expiry_countdown" style="color:#e67e22; font-size:13px; margin-top:6px;"></div>
                <p style="color:#888; font-size:12px; margin-top:8px;">El código expira en <strong>10 minutos</strong> y es de un solo uso.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-block" data-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<table class="table table-striped table-bordered table-hover" id="approvals_table" style="margin-top:16px;">
    <thead>
        <tr>
            <th>Hora</th>
            <th>Cajero</th>
            <th>Sucursal</th>
            <th>Artículo</th>
            <th style="text-align:right;">Precio Unit.</th>
            <th style="text-align:right;">Cant.</th>
            <th style="text-align:right;">Subtotal</th>
            <th style="text-align:right;">Descuento</th>
            <th style="text-align:right;">Precio Final</th>
            <th style="text-align:right;">Ahorro</th>
            <th style="text-align:center;">Espera</th>
            <th style="text-align:center;">Acción</th>
        </tr>
    </thead>
    <tbody id="approvals_tbody">
        <?php foreach ($pending as $row): ?>
            <?php
                $price     = (float)$row['item_price'];
                $qty       = (float)$row['item_quantity'];
                $discount  = (float)$row['discount'];
                $dtype     = (int)$row['discount_type'];
                $subtotal  = $price * $qty;
                if ($dtype === 1) {
                    $disc_amount = $discount * $qty;
                    $disc_label  = to_currency($discount) . ' c/u';
                } else {
                    $disc_amount = $subtotal * $discount / 100;
                    $disc_label  = number_format($discount, 1) . '%';
                }
                $final = $subtotal - $disc_amount;
                $created_ts = strtotime($row['created_at']);
            ?>
            <tr class="approval-row" data-id="<?= esc($row['approval_id']) ?>" data-created="<?= $created_ts ?>">
                <td><?= date('H:i:s', $created_ts) ?></td>
                <td><?= esc($row['cashier_name']) ?></td>
                <td><?= esc($row['location_name']) ?></td>
                <td><?= esc($row['item_name'] ?: '—') ?></td>
                <td style="text-align:right;"><?= to_currency($price) ?></td>
                <td style="text-align:right;"><?= number_format($qty, 0) ?></td>
                <td style="text-align:right;"><?= to_currency($subtotal) ?></td>
                <td style="text-align:right; color:#c0392b;"><strong><?= esc($disc_label) ?></strong></td>
                <td style="text-align:right; color:#27ae60;"><strong><?= to_currency($final) ?></strong></td>
                <td style="text-align:right; color:#e67e22;"><?= to_currency($disc_amount) ?></td>
                <td style="text-align:center;" class="wait_cell">—</td>
                <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-success btn-sm btn-approve" data-id="<?= esc($row['approval_id']) ?>">
                        <span class="glyphicon glyphicon-ok"></span> Aprobar
                    </button>
                    <button class="btn btn-danger btn-sm btn-reject" data-id="<?= esc($row['approval_id']) ?>" style="margin-left:4px;">
                        <span class="glyphicon glyphicon-remove"></span> Rechazar
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
(function () {
    var codeExpiryTimer = null;

    function fmt_wait(seconds) {
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        return (m > 0 ? m + 'm ' : '') + s + 's';
    }

    function update_wait_cells() {
        var now = Math.floor(Date.now() / 1000);
        $('.approval-row').each(function () {
            var created = parseInt($(this).data('created'), 10);
            var elapsed = now - created;
            $(this).find('.wait_cell').text(fmt_wait(elapsed));
            if (elapsed >= 300) $(this).addClass('warning');
        });
    }

    setInterval(update_wait_cells, 1000);
    update_wait_cells();

    function start_code_countdown(expiry_ts) {
        if (codeExpiryTimer) clearInterval(codeExpiryTimer);
        codeExpiryTimer = setInterval(function () {
            var remaining = Math.max(0, expiry_ts - Math.floor(Date.now() / 1000));
            $('#code_expiry_countdown').text('Expira en: ' + fmt_wait(remaining));
            if (remaining === 0) {
                clearInterval(codeExpiryTimer);
                $('#code_expiry_countdown').text('Código expirado');
            }
        }, 1000);
    }

    function esc_html(s) {
        return $('<div>').text(s || '').html();
    }

    function make_row(r) {
        return '<tr class="approval-row" data-id="' + r.approval_id + '" data-created="' + r.created_ts + '">' +
            '<td>' + esc_html(r.time_label) + '</td>' +
            '<td>' + esc_html(r.cashier_name) + '</td>' +
            '<td>' + esc_html(r.location_name) + '</td>' +
            '<td>' + esc_html(r.item_name) + '</td>' +
            '<td style="text-align:right;">' + r.price_fmt + '</td>' +
            '<td style="text-align:right;">' + r.qty_fmt + '</td>' +
            '<td style="text-align:right;">' + r.subtotal_fmt + '</td>' +
            '<td style="text-align:right; color:#c0392b;"><strong>' + esc_html(r.disc_label) + '</strong></td>' +
            '<td style="text-align:right; color:#27ae60;"><strong>' + r.final_fmt + '</strong></td>' +
            '<td style="text-align:right; color:#e67e22;">' + r.savings_fmt + '</td>' +
            '<td style="text-align:center;" class="wait_cell">—</td>' +
            '<td style="text-align:center; white-space:nowrap;">' +
                '<button class="btn btn-success btn-sm btn-approve" data-id="' + r.approval_id + '">' +
                    '<span class="glyphicon glyphicon-ok"></span> Aprobar' +
                '</button>' +
                '<button class="btn btn-danger btn-sm btn-reject" data-id="' + r.approval_id + '" style="margin-left:4px;">' +
                    '<span class="glyphicon glyphicon-remove"></span> Rechazar' +
                '</button>' +
            '</td>' +
        '</tr>';
    }

    function check_empty() {
        if ($('#approvals_tbody .approval-row').length === 0) {
            $('#no_pending').show();
        } else {
            $('#no_pending').hide();
        }
    }

    function remove_row(approval_id) {
        $('tr.approval-row[data-id="' + approval_id + '"]').fadeOut(300, function () {
            $(this).remove();
            check_empty();
        });
    }

    $(document).on('click', '.btn-approve', function () {
        var $btn = $(this);
        var approval_id = $btn.data('id');
        $btn.prop('disabled', true).html('<span class="glyphicon glyphicon-hourglass"></span>');

        $.ajax({
            url: '<?= base_url('discount_approvals/approve') ?>',
            type: 'POST',
            data: { approval_id: approval_id },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    var $code = $('#modal_code').empty();
                    res.code.split('').forEach(function (d) {
                        $code.append($('<div>').css({
                            width: '64px', height: '72px', lineHeight: '72px',
                            textAlign: 'center', fontSize: '44px', fontWeight: 'bold',
                            fontFamily: 'monospace', color: '#222',
                            background: '#f5f5f5', borderRadius: '6px', border: '2px solid #ddd'
                        }).text(d));
                    });
                    start_code_countdown(Math.floor(Date.now() / 1000) + 600);
                    $('#approval_code_modal').modal('show');
                    remove_row(approval_id);
                } else {
                    alert(res.message || 'Error al aprobar');
                    $btn.prop('disabled', false).html('<span class="glyphicon glyphicon-ok"></span> Aprobar');
                }
            },
            error: function () {
                alert('Error de conexión');
                $btn.prop('disabled', false).html('<span class="glyphicon glyphicon-ok"></span> Aprobar');
            }
        });
    });

    $(document).on('click', '.btn-reject', function () {
        var approval_id = $(this).data('id');
        if (!confirm('¿Rechazar esta solicitud de descuento?')) return;

        $.ajax({
            url: '<?= base_url('discount_approvals/reject') ?>',
            type: 'POST',
            data: { approval_id: approval_id },
            dataType: 'json',
            success: function () { remove_row(approval_id); }
        });
    });

    // Dynamic row sync — no page reload
    function refresh_rows() {
        $.ajax({
            url: '<?= base_url('discount_approvals/pendingRows') ?>',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                var rows      = res.rows || [];
                var serverIds = rows.map(function (r) { return r.approval_id; });

                // Remove rows no longer pending (processed by another session)
                $('.approval-row').each(function () {
                    var id = parseInt($(this).data('id'), 10);
                    if (serverIds.indexOf(id) === -1) {
                        $(this).fadeOut(300, function () { $(this).remove(); check_empty(); });
                    }
                });

                // Add new rows not yet in DOM
                rows.forEach(function (r) {
                    if ($('.approval-row[data-id="' + r.approval_id + '"]').length === 0) {
                        var $row = $(make_row(r)).hide();
                        $('#approvals_tbody').append($row);
                        $row.fadeIn(400);
                        check_empty();
                    }
                });

                // Update badge
                var count = rows.length;
                if (count > 0) {
                    $('#pending_badge').text(count + ' pendiente' + (count > 1 ? 's' : '')).show();
                } else {
                    $('#pending_badge').hide();
                }
            }
        });
    }

    setInterval(refresh_rows, 3000);
})();
</script>

<?= view('partial/footer') ?>
