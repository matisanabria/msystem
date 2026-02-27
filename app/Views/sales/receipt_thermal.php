<?php
/**
 * Thermal receipt template — 80 mm paper, browser printing.
 *
 * Variables from Sales::postComplete() / _load_sale_data():
 * @var array  $config
 * @var string $sale_id
 * @var string $transaction_date
 * @var string $transaction_time
 * @var string $employee
 * @var array  $cart
 * @var array  $payments
 * @var float  $total
 * @var float  $amount_change
 * @var string $customer        (optional – customer name)
 * @var string $tax_id          (optional – customer RUC)
 * @var string $customer_phone  (optional – customer phone)
 */

// Format amount as Paraguayan Guaraníes: no decimals, period = thousands separator
$fmt = static fn(float $n): string => number_format((int) round(abs($n)), 0, ',', '.');
?>

<style>
/* ── Screen preview ───────────────────────────── */
#thermal-receipt {
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    line-height: 1.3;
    width: 302px;   /* ≈ 80 mm at 96 dpi */
    margin: 12px auto;
    padding: 6px 4px;
    background: #fff;
    border: 1px dashed #bbb;
}

/* ── Print: 80 mm × 210 mm ────────────────────── */
@media print {
    @page {
        size: 80mm 210mm;
        margin: 4mm 3mm;
    }

    /* Ocultar todo el chrome de la página — solo imprimir el recibo */
    .topbar,
    .navbar,
    .navbar-default,
    #footer,
    .print_hide,
    #debug-bar,
    #debug-icon {
        display: none !important;
    }

    /* Quitar padding/margin de los contenedores Bootstrap */
    .wrapper,
    .container,
    .row {
        padding: 0 !important;
        margin: 0 !important;
    }

    #thermal-receipt {
        width: 100%;
        font-size: 10pt;
        line-height: 1.5;
        margin: 0;
        padding: 0;
        border: none;
    }

    .th-sep  { margin: 5px 0; }
    .th-sep2 { margin: 6px 0; }
}

/* ── Shared receipt styles ────────────────────── */
.th-c  { text-align: center; }
.th-r  { text-align: right; }
.th-b  { font-weight: bold; }

.th-sep  { border-top: 1px dashed #000; margin: 3px 0; }
.th-sep2 { border-top: 2px solid  #000; margin: 3px 0; }

/* Items table */
.th-items {
    width: 100%;
    border-collapse: collapse;
    font-family: inherit;
    font-size: inherit;
}
.th-items td, .th-items th {
    padding: 0 1px;
    vertical-align: top;
}
.col-desc  { width: 44%; text-align: left; }
.col-qty   { width: 8%;  text-align: right; }
.col-price { width: 24%; text-align: right; }
.col-total { width: 24%; text-align: right; }

/* Totals / payments rows: label left, value right */
.th-row {
    display: flex;
    justify-content: space-between;
    gap: 4px;
}
.th-row .lbl { flex: 1; text-align: right; }
.th-row .val { white-space: nowrap; }
</style>

<div id="thermal-receipt">

    <!-- HEADER -->
    <div class="th-c th-b"><?= esc($config['company'] ?? '') ?></div>

    <?php if (!empty($config['address'])): ?>
        <div class="th-c"><?= nl2br(esc($config['address'])) ?></div>
    <?php endif; ?>

    <?php if (!empty($config['phone'])): ?>
        <div class="th-c">Tel: <?= esc($config['phone']) ?></div>
    <?php endif; ?>

    <?php if (!empty($config['tax_id'])): ?>
        <div class="th-c">RUC: <?= esc($config['tax_id']) ?></div>
    <?php endif; ?>

    <div class="th-sep2"></div>

    <!-- DATE / TIME -->
    <div>Fecha: <?= esc($transaction_date) ?>&nbsp;&nbsp;<?= esc($transaction_time) ?></div>

    <!-- CUSTOMER (all fields are optional) -->
    <?php if (!empty($customer ?? '')): ?>
        <div>Cliente: <?= esc($customer) ?></div>
    <?php endif; ?>

    <?php if (!empty($tax_id ?? '')): ?>
        <div>RUC Cliente: <?= esc($tax_id) ?></div>
    <?php endif; ?>

    <?php if (!empty($customer_phone ?? '')): ?>
        <div>Tel. Cliente: <?= esc($customer_phone) ?></div>
    <?php endif; ?>

    <div class="th-sep"></div>

    <!-- ITEMS -->
    <table class="th-items">
        <thead>
            <tr>
                <th class="col-desc">DESCRIP.</th>
                <th class="col-qty">CANT</th>
                <th class="col-price">PRECIO</th>
                <th class="col-total">TOTAL</th>
            </tr>
            <tr><td colspan="4"><div class="th-sep"></div></td></tr>
        </thead>
        <tbody>
        <?php foreach ($cart as $item):
            if ($item['print_option'] !== PRINT_YES) {
                continue;
            }
            $qty         = (float) $item['quantity'];
            $qty_display = ($qty == (int) $qty) ? (string)(int) $qty : to_quantity_decimals($qty);
            $full_name   = ucfirst(trim($item['name'] . ' ' . ($item['attribute_values'] ?? '')));
        ?>
            <tr>
                <td class="col-desc" colspan="4"><?= esc($full_name) ?></td>
            </tr>
            <tr>
                <td class="col-desc" style="padding-left:6px;color:#555;"><?= esc($item['description']) ?></td>
                <td class="col-qty"><?= esc($qty_display) ?></td>
                <td class="col-price"><?= $fmt((float) $item['price']) ?></td>
                <td class="col-total"><?= $fmt((float) $item['discounted_total']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="th-sep"></div>

    <!-- TOTALS — IVA 10% incluido: IVA = total / 11 -->
    <?php $iva = (float) $total / 11; ?>

    <div class="th-row">
        <span class="lbl">SUBTOTAL:</span>
        <span class="val"><?= $fmt((float) $total) ?></span>
    </div>
    <div class="th-row">
        <span class="lbl">IVA 10%(inc):</span>
        <span class="val"><?= $fmt($iva) ?></span>
    </div>
    <div class="th-row th-b">
        <span class="lbl">TOTAL:</span>
        <span class="val"><?= $fmt((float) $total) ?></span>
    </div>

    <div class="th-sep"></div>

    <!-- PAYMENTS -->
    <div class="th-c">FORMA DE PAGO</div>
    <div class="th-sep"></div>

    <?php foreach ($payments as $payment):
        if ((bool) $payment['cash_adjustment']) {
            continue;
        }
        // Strip giftcard code suffix ("Giftcard:XXXXXX" → "Giftcard")
        $pay_label = explode(':', $payment['payment_type'])[0];
    ?>
        <div class="th-row">
            <span class="lbl"><?= esc($pay_label) ?></span>
            <span class="val"><?= $fmt((float) $payment['payment_amount']) ?></span>
        </div>
    <?php endforeach; ?>

    <div class="th-sep"></div>

    <!-- CHANGE -->
    <div class="th-row th-b">
        <span class="lbl">CAMBIO:</span>
        <span class="val">Gs.&nbsp;<?= $fmt(max(0.0, (float) $amount_change)) ?></span>
    </div>

    <div class="th-sep"></div>

    <!-- FOOTER -->
    <div>ID Venta: <?= esc($sale_id) ?>&nbsp;&nbsp;Cajero: <?= esc($employee) ?></div>

    <div class="th-sep"></div>

    <div class="th-c">¡Gracias por su compra!</div>
    <div class="th-c">Este no es un comprobante fiscal.</div>

    <div class="th-sep2"></div>

</div>
