<?php
/**
 * @var int   $person_id
 * @var array $permission_ids
 * @var array $grants
 */

$detailed_reports = [
    'reports_sales'      => 'detailed',
    'reports_customers'  => 'specific',
    'reports_suppliers'  => 'specific',
];

$allowed_summary_reports = ['reports_customers', 'reports_items', 'reports_suppliers', 'reports_sales'];
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    dialog_support.init("a.modal-dlg");
</script>

<?php
if (isset($error)) {
    echo '<div class="alert alert-dismissible alert-danger">' . esc($error) . '</div>';
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-list">&nbsp;</span><?= lang('Reports.summary_reports') ?></h3>
            </div>
            <div class="list-group">
                <?php foreach ($permission_ids as $permission_id) {
                    if (can_show_report($permission_id, ['inventory', 'receiving']) && in_array($permission_id, $allowed_summary_reports, true)) {
                        $link = get_report_link($permission_id, 'summary');
                ?>
                        <a class="list-group-item" href="<?= $link['path'] ?>"><?= $link['label'] ?></a>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-list-alt">&nbsp;</span><?= lang('Reports.detailed_reports') ?></h3>
            </div>
            <div class="list-group">
                <?php foreach ($detailed_reports as $report_name => $prefix) {
                    if (in_array($report_name, $permission_ids, true)) {
                        $link = get_report_link($report_name, $prefix);
                ?>
                        <a class="list-group-item" href="<?= $link['path'] ?>"><?= $link['label'] ?></a>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <?php if (in_array('reports_inventory', $permission_ids, true)) { ?>
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-book">&nbsp;</span><?= lang('Reports.inventory_reports') ?></h3>
                </div>
                <div class="list-group">
                    <?php
                    $inventory_low_report = get_report_link('reports_inventory_low');
                    $inventory_summary_report = get_report_link('reports_inventory_summary');
                    ?>
                    <a class="list-group-item" href="<?= $inventory_low_report['path'] ?>"><?= $inventory_low_report['label'] ?></a>
                    <a class="list-group-item" href="<?= $inventory_summary_report['path'] ?>"><?= $inventory_summary_report['label'] ?></a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <?php if (in_array('reports_sales', $permission_ids, true)) { ?>
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-usd">&nbsp;</span><?= lang('Reports.monthly_financial_summary_report') ?></h3>
                </div>
                <div class="list-group">
                    <a class="list-group-item" href="<?= site_url('reports/summary_monthly_sales') ?>">
                        <span class="glyphicon glyphicon-stats">&nbsp;</span><?= lang('Reports.monthly_financial_summary_report') ?>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="col-md-4">
        <?php if (in_array('reports_sales', $permission_ids, true)) { ?>
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-wrench">&nbsp;</span><?= lang('Reports.service_tickets_stats_report') ?></h3>
                </div>
                <div class="list-group">
                    <a class="list-group-item" href="<?= site_url('reports/service_tickets_sales') ?>">
                        <span class="glyphicon glyphicon-stats">&nbsp;</span><?= lang('Reports.service_tickets_stats_report') ?>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <?php if (in_array('reports_categories', $permission_ids, true) || in_array('reports_sales', $permission_ids, true)) { ?>
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-signal">&nbsp;</span><?= lang('Reports.graphical_reports') ?></h3>
                </div>
                <div class="list-group">
                    <a class="list-group-item" href="<?= site_url('reports/graphical_summary_trend_categories') ?>">
                        <span class="glyphicon glyphicon-stats">&nbsp;</span><?= lang('Reports.categories_trend_report') ?>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?= view('partial/footer') ?>
