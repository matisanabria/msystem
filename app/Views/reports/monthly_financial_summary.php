<?php
/**
 * @var string $title
 * @var string $subtitle
 * @var array  $headers
 * @var array  $data
 * @var array  $summary
 * @var array  $config
 */
?>

<?= view('partial/header') ?>

<style>
/* ── Layout ───────────────────────────────────────────── */
.mfs-wrap { padding: 4px 0 48px; }

.mfs-header { margin-bottom: 28px; }
.mfs-header h1 {
    font-size: 24px; font-weight: 700;
    color: #2C3E50; margin: 0 0 4px;
    letter-spacing: -.3px;
}
.mfs-header p {
    font-size: 13px; color: #95a5a6;
    margin: 0; font-weight: 500;
}

/* ── KPI Cards ────────────────────────────────────────── */
.mfs-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 36px;
}
@media (max-width: 1100px) { .mfs-kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px)  { .mfs-kpi-grid { grid-template-columns: repeat(2, 1fr); } }

.mfs-kpi {
    background: #fff;
    border-radius: 12px;
    padding: 20px 18px 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    border-left: 5px solid #bdc3c7;
    position: relative;
    overflow: hidden;
    transition: box-shadow .2s;
}
.mfs-kpi:hover { box-shadow: 0 6px 20px rgba(0,0,0,.12); }
.mfs-kpi-dot {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    margin-bottom: 12px;
    font-weight: 700;
}
.mfs-kpi-amount {
    font-size: 19px; font-weight: 800;
    color: #2C3E50; line-height: 1.2;
    margin-bottom: 3px;
    word-break: break-all;
}
.mfs-kpi-name {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    color: #b2bec3;
}
/* Color themes */
.mfs-kpi-ing  { border-left-color: #27ae60; }
.mfs-kpi-ing  .mfs-kpi-dot { background: #eafaf1; color: #27ae60; }
.mfs-kpi-cos  { border-left-color: #e74c3c; }
.mfs-kpi-cos  .mfs-kpi-dot { background: #fdedec; color: #e74c3c; }
.mfs-kpi-bru  { border-left-color: #2980b9; }
.mfs-kpi-bru  .mfs-kpi-dot { background: #eaf4fb; color: #2980b9; }
.mfs-kpi-egr  { border-left-color: #e67e22; }
.mfs-kpi-egr  .mfs-kpi-dot { background: #fef9e7; color: #e67e22; }
.mfs-kpi-pos  { border-left-color: #27ae60; }
.mfs-kpi-pos  .mfs-kpi-dot { background: #eafaf1; color: #27ae60; }
.mfs-kpi-neg  { border-left-color: #e74c3c; }
.mfs-kpi-neg  .mfs-kpi-dot { background: #fdedec; color: #e74c3c; }

/* ── Section label ────────────────────────────────────── */
.mfs-section-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .8px;
    color: #95a5a6;
    margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}
.mfs-section-label::after {
    content: ''; flex: 1;
    height: 1px; background: #ecf0f1;
}

/* ── Month cards grid ─────────────────────────────────── */
.mfs-months-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
@media (max-width: 1100px) { .mfs-months-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px)  { .mfs-months-grid { grid-template-columns: 1fr; } }

.mfs-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
}
.mfs-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.12); transform: translateY(-2px); }

.mfs-card-head {
    background: #2C3E50;
    color: #fff;
    padding: 13px 18px;
    font-size: 14px; font-weight: 700;
    letter-spacing: .2px;
}

.mfs-card-body { padding: 6px 0; }

.mfs-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 18px;
    border-bottom: 1px solid #f8f9fa;
}
.mfs-row:last-child { border-bottom: none; }
.mfs-row-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    color: #b2bec3;
}
.mfs-row-val {
    font-size: 13px; font-weight: 700;
    color: #2C3E50;
}
.mfs-row-val.v-pos { color: #27ae60; }
.mfs-row-val.v-neg { color: #e74c3c; }
.mfs-row-val.v-neu { color: #2980b9; }
.mfs-row-val.v-egr { color: #e67e22; }

.mfs-card-foot {
    padding: 11px 18px;
    display: flex; justify-content: space-between; align-items: center;
    font-size: 13px; font-weight: 800;
    letter-spacing: .1px;
}
.mfs-card-foot.fp { background: #eafaf1; color: #27ae60; }
.mfs-card-foot.fn { background: #fdedec; color: #e74c3c; }
.mfs-card-foot-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; opacity: .7; }
</style>

<div class="mfs-wrap">

    <div class="mfs-header">
        <h1><?= esc($title) ?></h1>
        <p><?= esc($subtitle) ?></p>
    </div>

    <?php if (empty($data)): ?>
        <div class="alert alert-info"><?= lang('Reports.no_reports_to_display') ?></div>
    <?php else:
        $netoPos = $summary['resultado_final'] >= 0;
        $brutoPos = $summary['resultado_bruto'] >= 0;
    ?>

    <!-- ── Totales ── -->
    <div class="mfs-section-label"><?= lang('Reports.total') ?></div>
    <div class="mfs-kpi-grid">

        <div class="mfs-kpi mfs-kpi-ing">
            <div class="mfs-kpi-dot">&#8593;</div>
            <div class="mfs-kpi-amount"><?= to_currency($summary['ingresos']) ?></div>
            <div class="mfs-kpi-name"><?= lang('Reports.total_ingresos') ?></div>
        </div>

        <div class="mfs-kpi mfs-kpi-cos">
            <div class="mfs-kpi-dot">&#9783;</div>
            <div class="mfs-kpi-amount"><?= to_currency($summary['costos']) ?></div>
            <div class="mfs-kpi-name"><?= lang('Reports.total_costos') ?></div>
        </div>

        <div class="mfs-kpi mfs-kpi-bru">
            <div class="mfs-kpi-dot">&#9632;</div>
            <div class="mfs-kpi-amount"><?= to_currency($summary['resultado_bruto']) ?></div>
            <div class="mfs-kpi-name"><?= lang('Reports.resultado_bruto') ?></div>
        </div>

        <div class="mfs-kpi mfs-kpi-egr">
            <div class="mfs-kpi-dot">&#8595;</div>
            <div class="mfs-kpi-amount"><?= to_currency($summary['egresos']) ?></div>
            <div class="mfs-kpi-name"><?= lang('Reports.total_egresos') ?></div>
        </div>

        <div class="mfs-kpi <?= $netoPos ? 'mfs-kpi-pos' : 'mfs-kpi-neg' ?>">
            <div class="mfs-kpi-dot"><?= $netoPos ? '&#9650;' : '&#9660;' ?></div>
            <div class="mfs-kpi-amount"><?= to_currency($summary['resultado_final']) ?></div>
            <div class="mfs-kpi-name"><?= lang('Reports.resultado_final') ?></div>
        </div>

    </div>

    <!-- ── Por mes ── -->
    <div class="mfs-section-label"><?= lang('Reports.monthly_financial_summary_report') ?></div>
    <div class="mfs-months-grid">
        <?php foreach ($data as $row):
            $isNeg = strpos($row['resultado_final'], '-') !== false;
            $bruNeg = strpos($row['resultado_bruto'], '-') !== false;
        ?>
        <div class="mfs-card">
            <div class="mfs-card-head"><?= esc($row['month']) ?></div>
            <div class="mfs-card-body">
                <div class="mfs-row">
                    <span class="mfs-row-label"><?= lang('Reports.total_ingresos') ?></span>
                    <span class="mfs-row-val v-pos"><?= $row['ingresos'] ?></span>
                </div>
                <div class="mfs-row">
                    <span class="mfs-row-label"><?= lang('Reports.total_costos') ?></span>
                    <span class="mfs-row-val v-neg"><?= $row['costos'] ?></span>
                </div>
                <div class="mfs-row">
                    <span class="mfs-row-label"><?= lang('Reports.resultado_bruto') ?></span>
                    <span class="mfs-row-val <?= $bruNeg ? 'v-neg' : 'v-neu' ?>"><?= $row['resultado_bruto'] ?></span>
                </div>
                <div class="mfs-row">
                    <span class="mfs-row-label"><?= lang('Reports.total_egresos') ?></span>
                    <span class="mfs-row-val v-egr"><?= $row['egresos'] ?></span>
                </div>
            </div>
            <div class="mfs-card-foot <?= $isNeg ? 'fn' : 'fp' ?>">
                <span class="mfs-card-foot-label"><?= lang('Reports.resultado_final') ?></span>
                <span><?= $row['resultado_final'] ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

</div>

<?= view('partial/footer') ?>
