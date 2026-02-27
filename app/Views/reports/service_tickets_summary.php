<?php
/**
 * @var string $title
 * @var array  $totals          keyed: received, waiting, in_repair, repaired, total
 * @var array  $technicians     rows: technician, pending, repaired, total
 * @var float  $pending_income
 * @var array  $config
 */

$fmt = static fn(float $n): string => number_format((int) round(abs($n)), 0, ',', '.');
?>

<?= view('partial/header') ?>

<style>
.sts-wrap { padding: 4px 0 48px; }

.sts-header { margin-bottom: 28px; }
.sts-header h1 { font-size: 24px; font-weight: 700; color: #2C3E50; margin: 0 0 4px; }

/* KPI grid */
.sts-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 36px;
}
@media (max-width: 1100px) { .sts-kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px)  { .sts-kpi-grid { grid-template-columns: repeat(2, 1fr); } }

.sts-kpi {
    background: #fff;
    border-radius: 12px;
    padding: 20px 18px 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    border-left: 5px solid #bdc3c7;
}
.sts-kpi-amount { font-size: 28px; font-weight: 800; color: #2C3E50; margin-bottom: 4px; }
.sts-kpi-name   { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #b2bec3; }

.sts-kpi-received  { border-left-color: #3498db; }
.sts-kpi-waiting   { border-left-color: #e67e22; }
.sts-kpi-in_repair { border-left-color: #9b59b6; }
.sts-kpi-repaired  { border-left-color: #27ae60; }
.sts-kpi-total     { border-left-color: #2C3E50; }
.sts-kpi-income    { border-left-color: #e74c3c; }

/* Section label */
.sts-section-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .8px;
    color: #95a5a6; margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}
.sts-section-label::after { content: ''; flex: 1; height: 1px; background: #ecf0f1; }

/* Technician table */
.sts-table {
    width: 100%;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    border-collapse: collapse;
    overflow: hidden;
}
.sts-table th {
    background: #2C3E50; color: #fff;
    padding: 12px 16px; text-align: left;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
}
.sts-table td { padding: 10px 16px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
.sts-table tr:last-child td { border-bottom: none; }
.sts-table tr:hover td { background: #fafafa; }
.sts-table .num { text-align: right; font-weight: 700; }
.badge-pending  { background: #e67e22; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; }
.badge-repaired { background: #27ae60; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; }
.badge-total    { background: #2C3E50; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; }
</style>

<div class="sts-wrap">

    <div class="sts-header">
        <h1><?= esc($title) ?></h1>
    </div>

    <!-- KPIs por estado -->
    <div class="sts-section-label"><?= lang('Reports.service_tickets_by_status') ?></div>
    <div class="sts-kpi-grid">

        <div class="sts-kpi sts-kpi-received">
            <div class="sts-kpi-amount"><?= $totals['received'] ?></div>
            <div class="sts-kpi-name"><?= lang('Service_tickets.status_received') ?></div>
        </div>

        <div class="sts-kpi sts-kpi-waiting">
            <div class="sts-kpi-amount"><?= $totals['waiting'] ?></div>
            <div class="sts-kpi-name"><?= lang('Service_tickets.status_waiting') ?></div>
        </div>

        <div class="sts-kpi sts-kpi-in_repair">
            <div class="sts-kpi-amount"><?= $totals['in_repair'] ?></div>
            <div class="sts-kpi-name"><?= lang('Service_tickets.status_in_repair') ?></div>
        </div>

        <div class="sts-kpi sts-kpi-repaired">
            <div class="sts-kpi-amount"><?= $totals['repaired'] ?></div>
            <div class="sts-kpi-name"><?= lang('Service_tickets.status_repaired') ?></div>
        </div>

        <div class="sts-kpi sts-kpi-total">
            <div class="sts-kpi-amount"><?= $totals['total'] ?></div>
            <div class="sts-kpi-name"><?= lang('Reports.total') ?></div>
        </div>

    </div>

    <!-- Ingreso estimado pendiente -->
    <div class="sts-section-label"><?= lang('Reports.service_tickets_pending_income') ?></div>
    <div class="sts-kpi-grid" style="grid-template-columns: repeat(1, 320px);">
        <div class="sts-kpi sts-kpi-income">
            <div class="sts-kpi-amount">Gs. <?= $fmt($pending_income) ?></div>
            <div class="sts-kpi-name"><?= lang('Reports.service_tickets_pending_income') ?></div>
        </div>
    </div>

    <!-- Por técnico -->
    <div class="sts-section-label"><?= lang('Reports.service_tickets_by_technician') ?></div>

    <?php if (empty($technicians)): ?>
        <div class="alert alert-info"><?= lang('Reports.no_reports_to_display') ?></div>
    <?php else: ?>
        <table class="sts-table">
            <thead>
                <tr>
                    <th><?= lang('Service_tickets.technician') ?></th>
                    <th style="text-align:right;"><?= lang('Reports.service_tickets_pending') ?></th>
                    <th style="text-align:right;"><?= lang('Service_tickets.status_repaired') ?></th>
                    <th style="text-align:right;"><?= lang('Reports.total') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($technicians as $row): ?>
                <tr>
                    <td><?= esc($row['technician']) ?></td>
                    <td class="num"><span class="badge-pending"><?= (int) $row['pending'] ?></span></td>
                    <td class="num"><span class="badge-repaired"><?= (int) $row['repaired'] ?></span></td>
                    <td class="num"><span class="badge-total"><?= (int) $row['total'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?= view('partial/footer') ?>
