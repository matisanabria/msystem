<?php
/**
 * @var string $controller_name
 * @var object $person_info
 * @var array $packages
 * @var int $selected_package
 * @var bool $use_destination_based_tax
 * @var string $sales_tax_code_label
 * @var string $employee
 * @var array $config
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("$controller_name/save/$person_info->person_id", ['id' => 'customer_form', 'class' => 'form-horizontal']) ?>

    <?php if (!empty($stats) || (!empty($mailchimp_info) && !empty($mailchimp_activity))): ?>
    <ul class="nav nav-tabs nav-justified" data-tabs="tabs">
        <li class="active" role="presentation">
            <a data-toggle="tab" href="#customer_basic_info"><?= lang('Customers.basic_information') ?></a>
        </li>
        <?php if (!empty($stats)) { ?>
            <li role="presentation">
                <a data-toggle="tab" href="#customer_stats_info"><?= lang('Customers.stats_info') ?></a>
            </li>
        <?php } ?>
        <?php if (!empty($mailchimp_info) && !empty($mailchimp_activity)) { ?>
            <li role="presentation">
                <a data-toggle="tab" href="#customer_mailchimp_info"><?= lang('Customers.mailchimp_info') ?></a>
            </li>
        <?php } ?>
    </ul>
    <?php endif; ?>

    <div class="tab-content">
        <div class="tab-pane fade in active" id="customer_basic_info">
            <fieldset>
                <?= form_hidden('consent', '1') ?>

                <?= view('people/form_basic_info') ?>

                <?php if ($config['customer_reward_enable']): ?>
                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.rewards_package'), 'rewards', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-8">
                            <?= form_dropdown(
                                'package_id',
                                $packages,
                                $selected_package,
                                'class="form-control input-sm"'
                            ) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.available_points'), 'available_points', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'available_points',
                                'id'       => 'available_points',
                                'class'    => 'form-control input-sm',
                                'value'    => $person_info->points,
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group form-group-sm">
                    <?= form_label(lang('Customers.date'), 'date', ['class' => 'control-label col-xs-3']) ?>
                    <div class="col-xs-8">
                        <div class="input-group">
                            <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
                            <?= form_input([
                                'name'     => 'date',
                                'id'       => 'datetime',
                                'class'    => 'form-control input-sm',
                                'value'    => to_datetime(strtotime($person_info->date)),
                                'readonly' => 'true'
                            ]) ?>
                        </div>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?= form_label(lang('Customers.employee'), 'employee', ['class' => 'control-label col-xs-3']) ?>
                    <div class="col-xs-8">
                        <?= form_input([
                            'name'     => 'employee',
                            'id'       => 'employee',
                            'class'    => 'form-control input-sm',
                            'value'    => $employee,
                            'readonly' => 'true'
                        ]) ?>
                    </div>
                </div>

                <?= form_hidden('employee_id', $person_info->employee_id) ?>
            </fieldset>
        </div>

        <?php if (!empty($stats)) { ?>
            <br>
            <div class="tab-pane" id="customer_stats_info">
                <fieldset>
                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.total'), 'total', ['class' => 'control-label col-xs-5']) ?>
                        <div class="col-xs-4">
                            <div class="input-group input-group-sm">
                                <?php if (!is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                                <?= form_input([
                                    'name'     => 'total',
                                    'id'       => 'total',
                                    'class'    => 'form-control input-sm',
                                    'value'    => to_currency_no_money($stats->total),
                                    'disabled' => ''
                                ]) ?>
                                <?php if (is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.max'), 'max', ['class' => 'control-label col-xs-5']) ?>
                        <div class="col-xs-4">
                            <div class="input-group input-group-sm">
                                <?php if (!is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                                <?= form_input([
                                    'name'     => 'max',
                                    'id'       => 'max',
                                    'class'    => 'form-control input-sm',
                                    'value'    => to_currency_no_money($stats->max),
                                    'disabled' => ''
                                ]) ?>
                                <?php if (is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.min'), 'min', ['class' => 'control-label col-xs-5']) ?>
                        <div class="col-xs-4">
                            <div class="input-group input-group-sm">
                                <?php if (!is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                                <?= form_input([
                                    'name'     => 'min',
                                    'id'       => 'min',
                                    'class'    => 'form-control input-sm',
                                    'value'    => to_currency_no_money($stats->min),
                                    'disabled' => ''
                                ]) ?>
                                <?php if (is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.average'), 'average', ['class' => 'control-label col-xs-5']) ?>
                        <div class="col-xs-4">
                            <div class="input-group input-group-sm">
                                <?php if (!is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                                <?= form_input([
                                    'name'     => 'average',
                                    'id'       => 'average',
                                    'class'    => 'form-control input-sm',
                                    'value'    => to_currency_no_money($stats->average),
                                    'disabled' => ''
                                ]) ?>
                                <?php if (is_right_side_currency_symbol()): ?>
                                    <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.quantity'), 'quantity', ['class' => 'control-label col-xs-5']) ?>
                        <div class="col-xs-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-addon input-sm"><b><?= '>' ?></b></span>
                                <?= form_input([
                                    'name'     => 'quantity',
                                    'id'       => 'quantity',
                                    'class'    => 'form-control input-sm',
                                    'value'    => to_quantity_decimals($stats->quantity),
                                    'disabled' => ''
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.avg_discount'), 'avg_discount', ['class' => 'control-label col-xs-5']) ?>
                        <div class="col-xs-4">
                            <div class="input-group input-group-sm">
                                <?= form_input([
                                    'name'     => 'avg_discount',
                                    'id'       => 'avg_discount',
                                    'class'    => 'form-control input-sm',
                                    'value'    => to_decimals($stats->avg_discount),
                                    'disabled' => ''
                                ]) ?>
                                <span class="input-group-addon input-sm"><b>%</b></span>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        <?php } ?>

        <?php if (!empty($mailchimp_info) && !empty($mailchimp_activity)) { ?>
            <div class="tab-pane" id="customer_mailchimp_info">
                <fieldset>
                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_status'), 'mailchimp_status', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_dropdown(
                                'mailchimp_status',
                                [
                                    'subscribed'   => 'subscribed',
                                    'unsubscribed' => 'unsubscribed',
                                    'cleaned'      => 'cleaned',
                                    'pending'      => 'pending'
                                ],
                                $mailchimp_info['status'],
                                ['id' => 'mailchimp_status', 'class' => 'form-control input-sm']
                            ) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_vip'), 'mailchimp_vip', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-1">
                            <?= form_checkbox('mailchimp_vip', 1, $mailchimp_info['vip'] == 1) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_member_rating'), 'mailchimp_member_rating', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'mailchimp_member_rating',
                                'class'    => 'form-control input-sm',
                                'value'    => $mailchimp_info['member_rating'],
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_activity_total'), 'mailchimp_activity_total', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'mailchimp_activity_total',
                                'class'    => 'form-control input-sm',
                                'value'    => $mailchimp_activity['total'],
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_activity_lastopen'), 'mailchimp_activity_lastopen', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'mailchimp_activity_lastopen',
                                'class'    => 'form-control input-sm',
                                'value'    => $mailchimp_activity['lastopen'],
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_activity_open'), 'mailchimp_activity_open', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'mailchimp_activity_open',
                                'class'    => 'form-control input-sm',
                                'value'    => $mailchimp_activity['open'],
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_activity_click'), 'mailchimp_activity_click', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'mailchimp_activity_click',
                                'class'    => 'form-control input-sm',
                                'value'    => $mailchimp_activity['click'],
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_activity_unopen'), 'mailchimp_activity_unopen', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'mailchimp_activity_unopen',
                                'class'    => 'form-control input-sm',
                                'value'    => $mailchimp_activity['unopen'],
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group form-group-sm">
                        <?= form_label(lang('Customers.mailchimp_email_client'), 'mailchimp_email_client', ['class' => 'control-label col-xs-3']) ?>
                        <div class="col-xs-4">
                            <?= form_input([
                                'name'     => 'mailchimp_email_client',
                                'class'    => 'form-control input-sm',
                                'value'    => $mailchimp_info['email_client'],
                                'disabled' => ''
                            ]) ?>
                        </div>
                    </div>
                </fieldset>
            </div>
        <?php } ?>
    </div>

<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        $('#customer_form').validate($.extend({
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        dialog_support.hide();
                        table_support.handle_submit("<?= $controller_name ?>", response);
                    },
                    dataType: 'json'
                });
            },

            errorLabelContainer: '#error_message_box',

            rules: {
                first_name: 'required',
                last_name: 'required',
                email: {
                    remote: {
                        url: "<?= "$controller_name/checkEmail" ?>",
                        type: 'POST',
                        data: {
                            'person_id': "<?= $person_info->person_id ?>"
                            // Email is posted by default
                        }
                    }
                }
            },

            messages: {
                first_name: "<?= lang('Common.first_name_required') ?>",
                last_name: "<?= lang('Common.last_name_required') ?>",
                email: "<?= lang('Customers.email_duplicate') ?>"
            }
        }, form_support.error));
    });
</script>
