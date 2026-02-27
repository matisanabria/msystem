<?php
/**
 * Service ticket thermal receipt — 80 mm paper.
 *
 * @var object $ticket
 * @var array  $config
 * @var string $ticket_date  (formatted datetime)
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

    <div class="st-c st-b">FICHA #<?= esc($ticket->ticket_id) ?></div>

    <div class="st-sep"></div>

    <div>Fecha:    <?= esc($ticket_date) ?></div>
    <div>Cliente:  <?= esc($ticket->customer_name ?? '—') ?></div>

    <?php
        $cid_type = $ticket->customer_identification_type ?? '';
        $cid_num  = $ticket->customer_identification ?? '';
        if (!empty($cid_num)):
            $cid_label = !empty($cid_type) ? $cid_type : 'Doc';
    ?>
        <div><?= esc($cid_label) ?>:     <?= esc($cid_num) ?></div>
    <?php endif; ?>

    <?php if (!empty($ticket->customer_phone)): ?>
        <div>Tel:      <?= esc($ticket->customer_phone) ?></div>
    <?php endif; ?>

    <div>Receptor: <?= esc($ticket->receiver_name ?? '—') ?></div>

    <div class="st-sep"></div>

    <div class="st-b">Dispositivo:</div>
    <div class="st-indent"><?= esc($ticket->device_name) ?></div>

    <div class="st-b" style="margin-top:4px;">Problema:</div>
    <div class="st-indent"><?= nl2br(esc($ticket->issue_description)) ?></div>

    <?php if (!empty($ticket->notes)): ?>
        <div class="st-b" style="margin-top:4px;">Notas:</div>
        <div class="st-indent"><?= nl2br(esc($ticket->notes)) ?></div>
    <?php endif; ?>

    <div class="st-sep"></div>

    <div class="st-c">Por favor conserve la ficha.</div>

</div>
