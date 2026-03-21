<?php
/**
 * @var string $controller_name
 * @var object $assistance_info
 * @var array $employees
 * @var array $statuses
 * @var string $selected_customer_name
 * @var string $selected_supplier_name
 * @var int|string $selected_customer
 * @var int|string $selected_supplier
 * @var int|string $selected_employee
 * @var string $selected_status
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("$controller_name/save/$assistance_info->assistance_id", ['id' => 'assistance_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="assistance_info">

        <!-- Product (autocomplete) -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.item'), 'item_search', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'         => 'item_search',
                    'id'           => 'item_search',
                    'class'        => 'form-control input-sm',
                    'placeholder'  => 'Buscar por nombre o código de barras...',
                    'value'        => $assistance_info->item_name ?? '',
                    'autocomplete' => 'off'
                ]) ?>
                <input type="hidden" name="item_id" id="item_id" value="<?= esc((string)($assistance_info->item_id ?? '')) ?>">
                <input type="hidden" name="item_name" id="item_name" value="<?= esc((string)($assistance_info->item_name ?? '')) ?>">
            </div>
        </div>

        <!-- Customer (autocomplete) -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.customer'), 'customer_search', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <div class="input-group">
                    <?= form_input([
                        'name'         => 'customer_search',
                        'id'           => 'customer_search',
                        'class'        => 'form-control input-sm',
                        'placeholder'  => 'Buscar cliente...',
                        'value'        => $selected_customer_name,
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

        <!-- Customer info panel -->
        <div id="customer_info_panel" class="col-xs-offset-3 col-xs-8" style="display:none; margin-bottom:8px;">
            <table class="table table-condensed" style="margin-bottom:0; font-size:12px;">
                <tbody id="customer_info_body"></tbody>
            </table>
        </div>

        <!-- Supplier (autocomplete) -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.supplier'), 'supplier_search', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'         => 'supplier_search',
                    'id'           => 'supplier_search',
                    'class'        => 'form-control input-sm',
                    'placeholder'  => 'Buscar proveedor...',
                    'value'        => $selected_supplier_name,
                    'autocomplete' => 'off'
                ]) ?>
                <input type="hidden" name="supplier_id" id="supplier_id" value="<?= esc((string)($selected_supplier ?? '')) ?>">
            </div>
        </div>

        <!-- Affects Stock checkbox -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.affects_stock'), 'affects_stock', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <div class="checkbox" style="margin-top:0;">
                    <label>
                        <?= form_checkbox([
                            'name'    => 'affects_stock',
                            'id'      => 'affects_stock',
                            'value'   => '1',
                            'checked' => ($assistance_info->affects_stock ?? 1) == 1
                        ]) ?>
                        <?= lang('Assistances.affects_stock_help') ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Problem Description -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.problem_description'), 'problem_description', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_textarea([
                    'name'        => 'problem_description',
                    'id'          => 'problem_description',
                    'class'       => 'form-control input-sm',
                    'value'       => $assistance_info->problem_description,
                    'rows'        => 3,
                    'placeholder' => 'Ej: No enciende, pantalla rota, no carga...'
                ]) ?>
            </div>
        </div>

        <!-- Status -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.status'), 'status', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('status', $statuses, $selected_status, ['class' => 'form-control', 'id' => 'status']) ?>
            </div>
        </div>

        <!-- Sent Date -->
        <div class="form-group form-group-sm date-field" id="sent_date_group">
            <?= form_label(lang('Assistances.sent_date'), 'sent_date', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'sent_date',
                    'id'    => 'sent_date',
                    'type'  => 'date',
                    'class' => 'form-control input-sm',
                    'value' => $assistance_info->sent_date
                ]) ?>
            </div>
        </div>

        <!-- Return Date -->
        <div class="form-group form-group-sm date-field" id="return_date_group">
            <?= form_label(lang('Assistances.return_date'), 'return_date', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'return_date',
                    'id'    => 'return_date',
                    'type'  => 'date',
                    'class' => 'form-control input-sm',
                    'value' => $assistance_info->return_date
                ]) ?>
            </div>
        </div>

        <!-- Delivered Date -->
        <div class="form-group form-group-sm date-field" id="delivered_date_group">
            <?= form_label(lang('Assistances.delivered_date'), 'delivered_date', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'delivered_date',
                    'id'    => 'delivered_date',
                    'type'  => 'date',
                    'class' => 'form-control input-sm',
                    'value' => $assistance_info->delivered_date
                ]) ?>
            </div>
        </div>

        <!-- Supplier Notes -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.supplier_notes'), 'supplier_notes', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_textarea([
                    'name'        => 'supplier_notes',
                    'id'          => 'supplier_notes',
                    'class'       => 'form-control input-sm',
                    'value'       => $assistance_info->supplier_notes,
                    'rows'        => 2,
                    'placeholder' => 'Respuesta del proveedor: diagnóstico, observaciones, si aplica garantía...'
                ]) ?>
            </div>
        </div>

        <!-- Resolution -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.resolution'), 'resolution', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_textarea([
                    'name'        => 'resolution',
                    'id'          => 'resolution',
                    'class'       => 'form-control input-sm',
                    'value'       => $assistance_info->resolution,
                    'rows'        => 2,
                    'placeholder' => 'Ej: Se reemplazó equipo, se reparó, no tiene garantía...'
                ]) ?>
            </div>
        </div>

        <!-- Employee -->
        <div class="form-group form-group-sm">
            <?= form_label(lang('Assistances.employee'), 'employee_id', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('employee_id', $employees, $selected_employee, ['class' => 'form-control', 'id' => 'employee_id']) ?>
            </div>
        </div>

    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {

        // Toggle date fields based on status
        function toggleDateFields() {
            var status = $('#status').val();
            $('#sent_date_group').toggle(status !== 'received');
            $('#return_date_group').toggle(status === 'returned' || status === 'delivered_to_customer');
            $('#delivered_date_group').toggle(status === 'delivered_to_customer');
        }
        toggleDateFields();
        $('#status').on('change', toggleDateFields);

        // Item autocomplete
        $('#item_search').autocomplete({
            source: '<?= site_url('items/suggest') ?>',
            minLength: 1,
            select: function(event, ui) {
                $('#item_search').val(ui.item.label);
                $('#item_id').val(ui.item.value);
                $('#item_name').val(ui.item.label);

                // Fetch item info to auto-fill supplier
                $.getJSON('<?= site_url('assistances/itemInfo') ?>/' + ui.item.value, function(info) {
                    if (info.supplier_id && info.supplier_name) {
                        $('#supplier_id').val(info.supplier_id);
                        $('#supplier_search').val(info.supplier_name);
                    }
                });

                return false;
            }
        });

        $('#item_search').on('input', function() {
            if ($(this).val() === '') {
                $('#item_id').val('');
                $('#item_name').val('');
            }
        });

        // Customer autocomplete + info panel
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

        // Load info for existing record
        <?php if (!empty($selected_customer)): ?>
        loadCustomerInfo(<?= (int)$selected_customer ?>);
        <?php endif; ?>

        // New customer button
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

        // Supplier autocomplete
        $('#supplier_search').autocomplete({
            source: '<?= site_url('suppliers/suggest') ?>',
            minLength: 1,
            select: function(event, ui) {
                $('#supplier_search').val(ui.item.label);
                $('#supplier_id').val(ui.item.value);
                return false;
            }
        });

        $('#supplier_search').on('input', function() {
            if ($(this).val() === '') {
                $('#supplier_id').val('');
            }
        });

        // Form validation
        $('#assistance_form').validate($.extend({
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
                item_name: 'required',
                problem_description: 'required',
                employee_id: 'required'
            },

            messages: {
                item_name: "<?= lang('Assistances.item_required') ?>",
                problem_description: "<?= lang('Assistances.problem_required') ?>",
                employee_id: "<?= lang('Assistances.employee_required') ?>"
            }
        }, form_support.error));
    });
</script>
