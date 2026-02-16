<?php
/**
 * @var string $controller_name
 * @var object $ticket_info
 * @var array $customers
 * @var array $employees
 * @var array $statuses
 * @var string $selected_customer
 * @var string $selected_receiver
 * @var string $selected_technician
 * @var string $selected_status
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("$controller_name/save/$ticket_info->ticket_id", ['id' => 'service_ticket_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="service_ticket_info">

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.customer'), 'customer_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('customer_id', $customers, $selected_customer, ['class' => 'form-control', 'id' => 'customer_id']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.receiver'), 'employee_id_receiver', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('employee_id_receiver', $employees, $selected_receiver, ['class' => 'form-control', 'id' => 'employee_id_receiver']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.technician'), 'employee_id_technician', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('employee_id_technician', $employees, $selected_technician, ['class' => 'form-control', 'id' => 'employee_id_technician']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.device_name'), 'device_name', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'device_name',
                    'id'    => 'device_name',
                    'class' => 'form-control input-sm',
                    'value' => $ticket_info->device_name
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.issue_description'), 'issue_description', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_textarea([
                    'name'  => 'issue_description',
                    'id'    => 'issue_description',
                    'class' => 'form-control input-sm',
                    'value' => $ticket_info->issue_description,
                    'rows'  => 3
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.status'), 'status', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('status', $statuses, $selected_status, ['class' => 'form-control', 'id' => 'status']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.estimated_price'), 'estimated_price', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'estimated_price',
                    'id'    => 'estimated_price',
                    'class' => 'form-control input-sm',
                    'value' => to_currency_no_money($ticket_info->estimated_price)
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Service_tickets.notes'), 'notes', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_textarea([
                    'name'  => 'notes',
                    'id'    => 'notes',
                    'class' => 'form-control input-sm',
                    'value' => $ticket_info->notes,
                    'rows'  => 3
                ]) ?>
            </div>
        </div>

    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#service_ticket_form').validate($.extend({
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        dialog_support.hide();
                        table_support.handle_submit("<?= esc($controller_name) ?>", response);
                    },
                    dataType: 'json'
                });
            },

            errorLabelContainer: '#error_message_box',

            rules: {
                employee_id_receiver: 'required',
                device_name: 'required',
                issue_description: 'required',
                status: 'required'
            },

            messages: {
                employee_id_receiver: "<?= lang('Service_tickets.receiver_required') ?>",
                device_name: "<?= lang('Service_tickets.device_name_required') ?>",
                issue_description: "<?= lang('Service_tickets.issue_description_required') ?>",
                status: "<?= lang('Service_tickets.status_required') ?>"
            }
        }, form_support.error));
    });
</script>
