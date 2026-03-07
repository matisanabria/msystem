<?php
/**
 * @var string $controller_name
 * @var object $ticket_info
 * @var array $employees
 * @var array $statuses
 * @var string $selected_customer_name
 * @var int|null $selected_customer
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
            <?= form_label(lang('Service_tickets.customer'), 'customer_search', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <div class="input-group">
                    <?= form_input([
                        'name'        => 'customer_search',
                        'id'          => 'customer_search',
                        'class'       => 'form-control input-sm',
                        'placeholder' => 'Buscar cliente...',
                        'value'       => $selected_customer_name,
                        'autocomplete' => 'off'
                    ]) ?>
                    <span class="input-group-btn">
                        <button type="button" id="btn_new_customer" class="btn btn-info btn-sm" title="<?= lang('Customers.new') ?>">
                            <span class="glyphicon glyphicon-user"></span>
                        </button>
                    </span>
                </div>
                <input type="hidden" name="customer_id" id="customer_id" value="<?= esc((string)($selected_customer ?? '')) ?>">
            </div>
        </div>

        <div id="customer_info_panel" class="col-xs-offset-3 col-xs-8" style="display:none; margin-bottom:8px;">
            <table class="table table-condensed" style="margin-bottom:0; font-size:12px;">
                <tbody id="customer_info_body"></tbody>
            </table>
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
        function loadCustomerInfo(personId) {
            if (!personId) {
                $('#customer_info_panel').hide();
                return;
            }
            $.getJSON('<?= site_url('customers/infoJson') ?>/' + personId, function(info) {
                var rows = '';
                if (info.name)  rows += '<tr><td><strong>Nombre</strong></td><td>' + $('<span>').text(info.name).html() + '</td></tr>';
                if (info.identification_type && info.identification) rows += '<tr><td><strong>' + $('<span>').text(info.identification_type).html() + '</strong></td><td>' + $('<span>').text(info.identification).html() + '</td></tr>';
                if (info.phone)   rows += '<tr><td><strong>Teléfono</strong></td><td>' + $('<span>').text(info.phone).html() + '</td></tr>';
                if (info.company) rows += '<tr><td><strong>Empresa</strong></td><td>' + $('<span>').text(info.company).html() + '</td></tr>';
                $('#customer_info_body').html(rows);
                $('#customer_info_panel').show();
            });
        }

        $('#customer_search').autocomplete({
            source: '<?= site_url('customers/suggest') ?>',
            minLength: 1,
            select: function(event, ui) {
                $('#customer_search').val(ui.item.label);
                $('#customer_id').val(ui.item.value);
                loadCustomerInfo(ui.item.value);
                return false;
            }
        });

        $('#customer_search').on('input', function() {
            if ($(this).val() === '') {
                $('#customer_id').val('');
                $('#customer_info_panel').hide();
            }
        });

        // Load info for existing ticket
        <?php if (!empty($selected_customer)): ?>
        loadCustomerInfo(<?= (int)$selected_customer ?>);
        <?php endif; ?>

        $('#btn_new_customer').on('click', function() {
            var capturedName = '';
            var customerDialog = BootstrapDialog.show({
                title: '<?= lang('Customers.new') ?>',
                message: function(dialog) {
                    var node = $('<div></div>');
                    $.get('<?= site_url('customers/view') ?>', function(data) {
                        node.html(data);
                        var origHide = dialog_support.hide;
                        var origHandleSubmit = table_support.handle_submit;
                        dialog_support.hide = function() {};
                        table_support.handle_submit = function(resource, response) {
                            dialog_support.hide = origHide;
                            table_support.handle_submit = origHandleSubmit;
                            if (response.success && response.id) {
                                customerDialog.close();
                                $('#customer_id').val(response.id);
                                $('#customer_search').val(capturedName);
                                loadCustomerInfo(response.id);
                                $.notify(response.message, { type: 'success' });
                            } else {
                                $.notify(response.message, { type: 'danger' });
                            }
                        };
                    });
                    return node;
                },
                buttons: [{
                    label: '<?= lang('Common.submit') ?>',
                    cssClass: 'btn-primary',
                    hotkey: 13,
                    action: function(dialog) {
                        var fn = $('[name="first_name"]', dialog.$modalBody).val() || '';
                        var ln = $('[name="last_name"]', dialog.$modalBody).val() || '';
                        capturedName = (fn + ' ' + ln).trim();
                        $('form', dialog.$modalBody).first().submit();
                    }
                }, {
                    label: 'Cancelar',
                    cssClass: 'btn-default',
                    action: function(dialog) {
                        dialog.close();
                    }
                }]
            });
        });

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
