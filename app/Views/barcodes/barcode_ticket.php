<?php
/**
 * @var array $items
 * @var array $barcode_config
 */

use App\Libraries\Barcode_lib;

$barcode_lib = new Barcode_lib();
?>
<!doctype html>
<html lang="<?= current_language_code() ?>">
<head>
    <meta charset="utf-8">
    <title>Tickets de productos</title>
    <style>
        /* === DIMENSIONES DE ETIQUETA === */
        :root {
            --label-w: 60mm;
            --label-h: 30mm;
        }

        @page {
            size: var(--label-w) var(--label-h);
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .ticket-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            padding: 0;
        }

        .ticket {
            width: var(--label-w);
            height: var(--label-h);
            border: none;
            display: flex;
            flex-direction: column;
            padding: 1.5mm;
            background: #fff;
            overflow: hidden;
        }

        .ticket:not(:last-child) {
            page-break-after: always;
            break-after: page;
        }

        .ticket-top {
            display: flex;
            flex-direction: row;
            flex: 1;
            gap: 1mm;
            align-items: center;
        }

        .ticket-logo {
            width: 13mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ticket-logo img {
            max-width: 12mm;
            max-height: 11mm;
            object-fit: contain;
        }

        .ticket-info {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1mm;
        }

        .ticket-info .product-name {
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            word-break: break-word;
        }

        .ticket-qr {
            width: 13mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ticket-qr img {
            width: 11mm;
            height: 11mm;
            object-fit: contain;
        }

        .ticket-qr .qr-label {
            font-size: 4pt;
            text-align: center;
            margin-top: 0.3mm;
        }

        .ticket-barcode {
            text-align: center;
            margin-top: 0.5mm;
        }

        .ticket-barcode svg {
            height: 6mm;
            width: 56mm;
        }

        .ticket-barcode .barcode-number {
            font-size: 5pt;
            letter-spacing: 0.5px;
            margin-top: 0.3mm;
        }

        .print-btn {
            display: block;
            margin: 8px auto;
            padding: 8px 24px;
            background: #337ab7;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .ticket { border: none; }
        }
    </style>
</head>
<body>
<div class="no-print" style="text-align:center; padding: 8px;">
    <button class="print-btn" onclick="window.print()">&#128438; Imprimir tickets</button>
</div>
<div class="ticket-grid">
    <?php foreach ($items as $item):
        $imei = $item['item_number'] ?? '';
        $name = $item['name'] ?? '';

        if (empty($imei)) {
            continue;
        }

        $barcode_svg = $barcode_lib->generate_receipt_barcode($imei);
    ?>
    <div class="ticket">
        <div class="ticket-top">
            <div class="ticket-logo">
                <img src="<?= base_url('images/ticket_logo.png') ?>" alt="Logo">
            </div>
            <div class="ticket-info">
                <span class="product-name"><?= esc($name) ?></span>
            </div>
            <div class="ticket-qr">
                <img src="<?= base_url('images/ticket_qr.png') ?>" alt="QR WhatsApp">
                <div class="qr-label">WhatsApp</div>
            </div>
        </div>
        <div class="ticket-barcode">
            <div><?= $barcode_svg ?></div>
            <div class="barcode-number"><?= esc($imei) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
