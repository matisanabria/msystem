<?php
/**
 * @var object $output_info
 * @var array $reasons
 * @var bool $is_new
 * @var string $controller_name
 */
?>

<?php if (!$is_new): ?>

    <table class="table table-condensed">
        <tbody>
            <tr>
                <td style="width:40%; color:#888;"><?= lang('Inventory_outputs.item') ?></td>
                <td><strong><?= esc($output_info->item_name ?? '') ?><?= !empty($output_info->item_number) ? ' [' . esc($output_info->item_number) . ']' : '' ?></strong></td>
            </tr>
            <tr>
                <td style="color:#888;"><?= lang('Inventory_outputs.location') ?></td>
                <td><?= esc($output_info->location_name ?? '') ?></td>
            </tr>
            <tr>
                <td style="color:#888;"><?= lang('Inventory_outputs.quantity') ?></td>
                <td><?= to_quantity_decimals($output_info->quantity ?? 0) ?></td>
            </tr>
            <tr>
                <td style="color:#888;"><?= lang('Inventory_outputs.reason') ?></td>
                <td><?= esc($reasons[$output_info->reason ?? ''] ?? '') ?></td>
            </tr>
            <?php if (!empty($output_info->comment)): ?>
                <tr>
                    <td style="color:#888;"><?= lang('Inventory_outputs.comment') ?></td>
                    <td><?= esc($output_info->comment) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td style="color:#888;"><?= lang('Inventory_outputs.date') ?></td>
                <td><?= !empty($output_info->created_at) ? to_datetime(strtotime($output_info->created_at)) : '' ?></td>
            </tr>
        </tbody>
    </table>

<?php else: ?>

    <div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
    <ul id="error_message_box" class="error_message_box"></ul>

    <?= form_open("$controller_name/save", ['id' => 'inventory_output_edit_form', 'class' => 'form-horizontal']) ?>
        <fieldset>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Inventory_outputs.item'), 'item_search', ['class' => 'required control-label col-xs-3']) ?>
                <div class="col-xs-8">
                    <?= form_input(['name' => 'item_search', 'id' => 'item_search', 'class' => 'form-control input-sm', 'placeholder' => lang('Inventory_outputs.item_search_placeholder')]) ?>
                    <?= form_hidden('item_id', '') ?>
                </div>
            </div>

            <div id="item_info_panel" class="form-group form-group-sm" style="display:none;">
                <div class="col-xs-offset-3 col-xs-8">
                    <div class="alert alert-info" style="padding:8px 12px; margin-bottom:0;">
                        <span id="item_info_location"></span> —
                        <?= lang('Inventory_outputs.stock_available') ?>: <strong id="item_info_stock"></strong>
                    </div>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Inventory_outputs.quantity'), 'quantity', ['class' => 'required control-label col-xs-3']) ?>
                <div class="col-xs-4">
                    <?= form_input(['name' => 'quantity', 'id' => 'quantity', 'class' => 'form-control input-sm', 'onClick' => 'this.select();']) ?>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Inventory_outputs.reason'), 'reason', ['class' => 'required control-label col-xs-3']) ?>
                <div class="col-xs-8">
                    <?= form_dropdown('reason', $reasons, '', ['id' => 'reason', 'class' => 'form-control']) ?>
                </div>
            </div>

            <div id="comment_group" class="form-group form-group-sm" style="display:none;">
                <?= form_label(lang('Inventory_outputs.comment'), 'comment', ['class' => 'required control-label col-xs-3']) ?>
                <div class="col-xs-8">
                    <?= form_textarea(['name' => 'comment', 'id' => 'comment', 'class' => 'form-control input-sm']) ?>
                </div>
            </div>

        </fieldset>
    <?= form_close() ?>

    <script type="text/javascript">
        $(document).ready(function() {
            var suggest_cache = {};

            $('#item_search').autocomplete({
                source: "<?= esc("$controller_name/itemSuggest") ?>",
                minChars: 2,
                delay: 300,
                select: function(event, ui) {
                    event.preventDefault();
                    $('#item_search').val(ui.item.label);
                    $('[name="item_id"]').val(ui.item.value);

                    $.getJSON("<?= esc("$controller_name/itemInfo") ?>/" + ui.item.value, function(response) {
                        if (!response.success) {
                            $('#item_info_panel').hide();
                            $('[name="item_id"]').val('');
                            alert(response.message);
                            return;
                        }

                        $('#item_info_location').text(response.location_name);
                        $('#item_info_stock').text(response.quantity);
                        $('#item_info_panel').show();
                    });
                }
            });

            $('#reason').on('change', function() {
                if ($(this).val() === 'other') {
                    $('#comment_group').show();
                } else {
                    $('#comment_group').hide();
                    $('#comment').val('');
                }
            }).trigger('change');

            $('#inventory_output_edit_form').validate($.extend({
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

                ignore: '',

                rules: {
                    item_id: {
                        required: true,
                        min: 1
                    },
                    quantity: {
                        required: true,
                        remote: "<?= "$controller_name/checkNumeric" ?>"
                    },
                    comment: {
                        required: function() {
                            return $('#reason').val() === 'other';
                        }
                    }
                },

                messages: {
                    item_id: {
                        required: "<?= lang('Inventory_outputs.item_required') ?>",
                        min: "<?= lang('Inventory_outputs.item_required') ?>"
                    },
                    quantity: {
                        required: "<?= lang('Inventory_outputs.quantity_required') ?>",
                        remote: "<?= lang('Inventory_outputs.quantity_number') ?>"
                    },
                    comment: {
                        required: "<?= lang('Inventory_outputs.comment_required_other') ?>"
                    }
                }
            }, form_support.error));
        });
    </script>

<?php endif; ?>
