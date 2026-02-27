<?php
/**
 * @var object $ticket
 * @var array  $config
 * @var string $ticket_date
 */
?>
<?= view('partial/header') ?>

<style>
/* ── Screen ───────────────────────────────────── */
.st-receipt {
    font-family: monospace;
    font-size: 12px;
    line-height: 1.4;
    width: 302px;
    margin: 12px auto;
    padding: 8px 6px;
    background: #fff;
    border: 1px dashed #bbb;
}

/* ── Print ────────────────────────────────────── */
@media print {
    @page {
        size: 80mm auto;
        margin: 4mm 3mm;
    }

    .topbar, .navbar, .navbar-default,
    #footer, .print_hide, #debug-bar, #debug-icon {
        display: none !important;
    }

    .wrapper, .container, .row {
        padding: 0 !important;
        margin: 0 !important;
    }

    .st-receipt {
        width: 100%;
        font-size: 10pt;
        line-height: 1.5;
        margin: 0;
        padding: 0;
        border: none;
    }

}

/* ── Shared ───────────────────────────────────── */
.st-c      { text-align: center; }
.st-b      { font-weight: bold; }
.st-sep    { border-top: 1px dashed #000; margin: 5px 0; }
.st-indent { padding-left: 10px; word-break: break-word; }
</style>

<div class="print_hide" style="text-align: right; margin: 8px;">
    <a href="javascript:window.print();">
        <div class="btn btn-info btn-sm">
            <span class="glyphicon glyphicon-print">&nbsp;</span> Imprimir
        </div>
    </a>
    <a href="javascript:history.back();">
        <div class="btn btn-default btn-sm">Volver</div>
    </a>
</div>

<?= view('service_tickets/receipt_thermal', ['ticket' => $ticket, 'config' => $config, 'ticket_date' => $ticket_date]) ?>

<?= view('partial/footer') ?>
