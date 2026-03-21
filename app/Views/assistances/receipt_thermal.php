<?php
/**
 * Assistance thermal receipt — 80 mm paper.
 *
 * @var object $assistance
 * @var array  $config
 * @var string $assistance_date  (formatted datetime)
 */
?>
<div class="st-receipt">

    <!-- HEADER -->
    <div class="st-c st-b"><?= esc($config['company'] ?? '') ?></div>

    <?php if (!empty($config['address'])): ?>
        <div class="st-c"><?= nl2br(esc($config['address'])) ?></div>
    <?php endif; ?>

    <?php if (!empty($config['phone'])): ?>
        <div class="st-c">Tel: <?= esc($config['phone']) ?></div>
    <?php endif; ?>

    <div class="st-sep"></div>

    <div class="st-c st-b"><?= lang('Assistances.receipt_title') ?> #<?= esc($assistance->assistance_id) ?></div>

    <div class="st-sep"></div>

    <div>Fecha:      <?= esc($assistance_date) ?></div>

    <?php if (!empty($assistance->customer_name)): ?>
        <div>Cliente:    <?= esc($assistance->customer_name) ?></div>
    <?php endif; ?>

    <?php
        $cid_type = $assistance->customer_identification_type ?? '';
        $cid_num  = $assistance->customer_identification ?? '';
        if (!empty($cid_num)):
            $cid_label = !empty($cid_type) ? $cid_type : 'Doc';
    ?>
        <div><?= esc($cid_label) ?>:     <?= esc($cid_num) ?></div>
    <?php endif; ?>

    <?php if (!empty($assistance->customer_phone)): ?>
        <div>Tel:        <?= esc($assistance->customer_phone) ?></div>
    <?php endif; ?>

    <div class="st-sep"></div>

    <div class="st-b">Producto:</div>
    <div class="st-indent"><?= esc($assistance->item_name) ?></div>

    <?php if (!empty($assistance->supplier_name)): ?>
        <div class="st-b" style="margin-top:4px;">Proveedor:</div>
        <div class="st-indent"><?= esc($assistance->supplier_name) ?></div>
    <?php endif; ?>

    <div class="st-b" style="margin-top:4px;">Problema:</div>
    <div class="st-indent"><?= nl2br(esc($assistance->problem_description)) ?></div>

    <div class="st-sep"></div>

    <?php
        $status_labels = [
            'received'              => lang('Assistances.status_received'),
            'sent_to_supplier'      => lang('Assistances.status_sent_to_supplier'),
            'in_repair'             => lang('Assistances.status_in_repair'),
            'returned'              => lang('Assistances.status_returned'),
            'delivered_to_customer' => lang('Assistances.status_delivered_to_customer'),
        ];
    ?>
    <div>Estado: <?= esc($status_labels[$assistance->status] ?? $assistance->status) ?></div>

    <div class="st-sep"></div>

    <div class="st-c"><?= lang('Assistances.keep_receipt') ?></div>

</div>
