<?php
/**
 * Thermal receipt template — 80 mm paper, browser printing.
 *
 * Variables from Sales::postComplete() / _load_sale_data():
 * @var array  $config
 * @var string $sale_id
 * @var string $transaction_date
 * @var string $transaction_time   (full datetime: date + time)
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
    font-family: monospace;
    font-size: 12px;
    line-height: 1.4;
    width: 302px;
    margin: 12px auto;
    padding: 8px 6px;
    background: #fff;
    border: 1px dashed #bbb;
}

/* ── Print: 80 mm × auto ──────────────────────── */
@media print {
    @page {
        size: 80mm auto;
        margin: 4mm 3mm;
    }

    .topbar,
    .navbar,
    .navbar-default,
    #footer,
    .print_hide,
    #debug-bar,
    #debug-icon {
        display: none !important;
    }

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
}

/* ── Shared ───────────────────────────────────── */
.th-c { text-align: center; }
.th-b { font-weight: bold; }

.th-sep { border-top: 1px dashed #000; margin: 5px 0; }

/* Totals / payments rows: label left, value right */
.th-row {
    display: flex;
    justify-content: space-between;
    gap: 4px;
}
.th-row .lbl { flex: 1; text-align: right; }
.th-row .val { white-space: nowrap; }

/* Items */
.th-item-name {
    word-break: break-word;
}
.th-item-detail {
    display: flex;
    justify-content: space-between;
    padding-left: 10px;
    margin-bottom: 4px;
    color: #333;
}
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

    <div class="th-sep"></div>

    <!-- DATE / TIME — $transaction_time already contains date + time -->
    <div>Fecha: <?= esc($transaction_time) ?></div>

    <!-- CUSTOMER (all fields optional) -->
    <?php if (!empty($customer ?? '')): ?>
        <div>Cliente: <?= esc($customer) ?></div>
    <?php endif; ?>

    <?php
        $cid_type = $customer_identification_type ?? '';
        $cid_num  = $customer_identification ?? '';
        if (!empty($cid_num)):
            $cid_label = !empty($cid_type) ? $cid_type : 'Doc';
    ?>
        <div><?= esc($cid_label) ?>: <?= esc($cid_num) ?></div>
    <?php endif; ?>

    <?php if (!empty($customer_phone ?? '')): ?>
        <div>Tel: <?= esc($customer_phone) ?></div>
    <?php endif; ?>

    <div class="th-sep"></div>

    <!-- ITEMS -->
    <?php foreach ($cart as $item):
        if ($item['print_option'] !== PRINT_YES) {
            continue;
        }
        $qty         = (float) $item['quantity'];
        $qty_display = ($qty == (int) $qty) ? (string)(int) $qty : to_quantity_decimals($qty);
        $full_name   = ucfirst(trim($item['name'] . ' ' . ($item['attribute_values'] ?? '')));
    ?>
        <div class="th-item-name th-b"><?= esc($full_name) ?></div>
        <div class="th-item-detail">
            <span><?= esc($qty_display) ?> x <?= $fmt((float) $item['price']) ?></span>
            <span><?= $fmt((float) $item['discounted_total']) ?></span>
        </div>
    <?php endforeach; ?>

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
    <?php foreach ($payments as $payment):
        if ((bool) $payment['cash_adjustment']) {
            continue;
        }
        $pay_label = explode(':', $payment['payment_type'])[0];
    ?>
        <div class="th-row">
            <span class="lbl"><?= esc($pay_label) ?></span>
            <span class="val"><?= $fmt((float) $payment['payment_amount']) ?></span>
        </div>
    <?php endforeach; ?>

    <div class="th-row th-b" style="margin-top: 3px;">
        <span class="lbl">CAMBIO:</span>
        <span class="val">Gs. <?= $fmt(max(0.0, (float) $amount_change)) ?></span>
    </div>

    <div class="th-sep"></div>

    <!-- FOOTER -->
    <div>ID Venta: <?= esc($sale_id) ?></div>
    <div>Cajero: <?= esc($employee) ?></div>

    <div class="th-sep"></div>

    <div class="th-c">¡Gracias por su compra!</div>
    <div class="th-c">No es un comprobante fiscal.</div>

</div>
