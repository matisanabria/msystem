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

<div id="page_title"><?= esc($title) ?></div>
<div id="page_subtitle"><?= esc($subtitle) ?></div>

<?php if (empty($data)): ?>
    <div class="alert alert-info"><?= lang('Reports.no_reports_to_display') ?></div>
<?php else: ?>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-condensed">
        <thead>
            <tr class="active">
                <th><?= lang('Reports.month') ?></th>
                <th class="text-right"><?= lang('Reports.total_ingresos') ?></th>
                <th class="text-right"><?= lang('Reports.total_costos') ?></th>
                <th class="text-right"><?= lang('Reports.resultado_bruto') ?></th>
                <th class="text-right"><?= lang('Reports.total_egresos') ?></th>
                <th class="text-right"><?= lang('Reports.resultado_final') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= esc($row['month']) ?></td>
                <td class="text-right"><?= $row['ingresos'] ?></td>
                <td class="text-right"><?= $row['costos'] ?></td>
                <td class="text-right"><?= $row['resultado_bruto'] ?></td>
                <td class="text-right"><?= $row['egresos'] ?></td>
                <td class="text-right"><?= $row['resultado_final'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="active">
                <th><strong><?= lang('Reports.total') ?></strong></th>
                <th class="text-right"><strong><?= to_currency($summary['ingresos']) ?></strong></th>
                <th class="text-right"><strong><?= to_currency($summary['costos']) ?></strong></th>
                <th class="text-right"><strong><?= to_currency($summary['resultado_bruto']) ?></strong></th>
                <th class="text-right"><strong><?= to_currency($summary['egresos']) ?></strong></th>
                <th class="text-right"><strong><?= to_currency($summary['resultado_final']) ?></strong></th>
            </tr>
        </tfoot>
    </table>
</div>

<?php endif; ?>

<?= view('partial/footer') ?>
