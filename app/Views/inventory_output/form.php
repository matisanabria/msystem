<?php
/**
 * @var object $output_info
 * @var array $reasons
 * @var bool $is_new
 * @var string $controller_name
 * @var array $stock_locations
 * @var int $output_location_id
 * @var bool $show_location_select
 */
?>

<?php if (!$is_new): ?>

    <fieldset class="form-horizontal">

        <div class="form-group form-group-sm">
            <?= form_label(lang('Inventory_outputs.barcode'), 'view_barcode', ['class' => 'control-label col-3']) ?>
            <div class="col-8">
                <div class="input-group">
                    <span class="input-group-text form-control-sm"><span class="bi bi-upc-scan"></span></span>
                    <?= form_input(['name' => 'view_barcode', 'id' => 'view_barcode', 'class' => 'form-control form-control-sm', 'value' => $output_info->item_number ?? '', 'disabled' => 'disabled']) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Inventory_outputs.item_name'), 'view_item_name', ['class' => 'control-label col-3']) ?>
            <div class="col-8">
                <?= form_input(['name' => 'view_item_name', 'id' => 'view_item_name', 'class' => 'form-control form-control-sm', 'value' => $output_info->item_name ?? '', 'disabled' => 'disabled']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Common.location'), 'view_location', ['class' => 'control-label col-3']) ?>
            <div class="col-8">
                <div class="input-group">
                    <span class="input-group-text form-control-sm"><span class="bi bi-geo-alt"></span></span>
                    <?= form_input(['name' => 'view_location', 'id' => 'view_location', 'class' => 'form-control form-control-sm', 'value' => $output_info->location_name ?? '', 'disabled' => 'disabled']) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Inventory_outputs.quantity'), 'view_quantity', ['class' => 'control-label col-3']) ?>
            <div class="col-4">
                <?= form_input(['name' => 'view_quantity', 'id' => 'view_quantity', 'class' => 'form-control form-control-sm', 'value' => to_quantity_decimals($output_info->quantity ?? 0), 'disabled' => 'disabled']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Inventory_outputs.reason'), 'view_reason', ['class' => 'control-label col-3']) ?>
            <div class="col-8">
                <?= form_input(['name' => 'view_reason', 'id' => 'view_reason', 'class' => 'form-control form-control-sm', 'value' => $reasons[$output_info->reason ?? ''] ?? '', 'disabled' => 'disabled']) ?>
            </div>
        </div>

        <?php if (!empty($output_info->comment)): ?>
        <div class="form-group form-group-sm">
            <?= form_label(lang('Inventory_outputs.comment'), 'view_comment', ['class' => 'control-label col-3']) ?>
            <div class="col-8">
                <?= form_textarea(['name' => 'view_comment', 'id' => 'view_comment', 'class' => 'form-control form-control-sm', 'value' => $output_info->comment ?? '', 'disabled' => 'disabled']) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Inventory_outputs.date'), 'view_date', ['class' => 'control-label col-3']) ?>
            <div class="col-8">
                <?= form_input(['name' => 'view_date', 'id' => 'view_date', 'class' => 'form-control form-control-sm', 'value' => !empty($output_info->created_at) ? to_datetime(strtotime($output_info->created_at)) : '', 'disabled' => 'disabled']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Inventory_outputs.employee'), 'view_employee', ['class' => 'control-label col-3']) ?>
            <div class="col-8">
                <?= form_input(['name' => 'view_employee', 'id' => 'view_employee', 'class' => 'form-control form-control-sm', 'value' => trim(($output_info->first_name ?? '') . ' ' . ($output_info->last_name ?? '')), 'disabled' => 'disabled']) ?>
            </div>
        </div>

    </fieldset>

<?php else: ?>

    <div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
    <ul id="error_message_box" class="error_message_box"></ul>

    <?= form_open("$controller_name/save", ['id' => 'inventory_output_edit_form', 'class' => 'form-horizontal']) ?>
        <fieldset>

            <?php if (!empty($show_location_select) && !empty($stock_locations)): ?>
            <div class="form-group form-group-sm">
                <?= form_label(lang('Common.location'), 'location_id', ['class' => 'control-label col-3']) ?>
                <div class="col-8">
                    <?= form_dropdown('location_id', $stock_locations, (string)$output_location_id, ['id' => 'location_id', 'class' => 'form-select']) ?>
                </div>
            </div>
            <?php else: ?>
                <?= form_hidden('location_id', (string)($output_location_id ?? (string)array_key_first($stock_locations ?? []))) ?>
            <?php endif; ?>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Inventory_outputs.item'), 'item_search', ['class' => 'required control-label col-3']) ?>
                <div class="col-8">
                    <?= form_input(['name' => 'item_search', 'id' => 'item_search', 'class' => 'form-control form-control-sm', 'placeholder' => lang('Inventory_outputs.item_search_placeholder'), 'autocomplete' => 'off']) ?>
                    <?= form_hidden('item_id', '') ?>
                </div>
            </div>

            <div id="item_info_panel" style="display:none;">

                <div class="form-group form-group-sm">
                    <?= form_label(lang('Inventory_outputs.barcode'), 'item_info_barcode', ['class' => 'control-label col-3']) ?>
                    <div class="col-8">
                        <div class="input-group">
                            <span class="input-group-text form-control-sm"><span class="bi bi-upc-scan"></span></span>
                            <?= form_input(['name' => 'item_info_barcode', 'id' => 'item_info_barcode', 'class' => 'form-control form-control-sm', 'disabled' => 'disabled']) ?>
                        </div>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?= form_label(lang('Inventory_outputs.item_name'), 'item_info_name', ['class' => 'control-label col-3']) ?>
                    <div class="col-8">
                        <?= form_input(['name' => 'item_info_name', 'id' => 'item_info_name', 'class' => 'form-control form-control-sm', 'disabled' => 'disabled']) ?>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?= form_label(lang('Inventory_outputs.category'), 'item_info_category', ['class' => 'control-label col-3']) ?>
                    <div class="col-8">
                        <div class="input-group">
                            <span class="input-group-text form-control-sm"><span class="bi bi-tag"></span></span>
                            <?= form_input(['name' => 'item_info_category', 'id' => 'item_info_category', 'class' => 'form-control form-control-sm', 'disabled' => 'disabled']) ?>
                        </div>
                    </div>
                </div>

                <div class="form-group form-group-sm">
                    <?= form_label(lang('Inventory_outputs.stock_available'), 'item_info_stock', ['class' => 'control-label col-3']) ?>
                    <div class="col-8">
                        <?= form_input(['name' => 'item_info_stock', 'id' => 'item_info_stock', 'class' => 'form-control form-control-sm', 'disabled' => 'disabled']) ?>
                    </div>
                </div>

            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Inventory_outputs.quantity'), 'quantity', ['class' => 'required control-label col-3']) ?>
                <div class="col-4">
                    <?= form_input(['name' => 'quantity', 'id' => 'quantity', 'class' => 'form-control form-control-sm', 'onClick' => 'this.select();']) ?>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Inventory_outputs.reason'), 'reason', ['class' => 'required control-label col-3']) ?>
                <div class="col-8">
                    <?= form_dropdown('reason', $reasons, '', ['id' => 'reason', 'class' => 'form-select']) ?>
                </div>
            </div>

            <div id="comment_group" class="form-group form-group-sm" style="display:none;">
                <?= form_label(lang('Inventory_outputs.comment'), 'comment', ['class' => 'required control-label col-3']) ?>
                <div class="col-8">
                    <?= form_textarea(['name' => 'comment', 'id' => 'comment', 'class' => 'form-control form-control-sm']) ?>
                </div>
            </div>

        </fieldset>
    <?= form_close() ?>

    <script type="text/javascript">
        $(document).ready(function() {

            function loadItemInfo(itemId) {
                if (!itemId) {
                    $('#item_info_panel').hide();
                    return;
                }
                $.getJSON('<?= site_url("$controller_name/itemInfo") ?>/' + itemId, function(info) {
                    $('#item_info_barcode').val(info.item_number || '');
                    $('#item_info_name').val(info.name || '');
                    $('#item_info_category').val(info.category || '');
                    $('#item_info_stock').val(info.quantity);

                    $('#item_info_panel').show();
                });
            }

            function getItemSuggestUrl() {
                return '<?= site_url("$controller_name/suggestItems") ?>?location_id=' + encodeURIComponent($('[name="location_id"]').val());
            }

            $('#item_search').autocomplete({
                source: function(request, response) {
                    $.getJSON(getItemSuggestUrl(), {term: request.term}, response);
                },
                minLength: 1,
                select: function(event, ui) {
                    $('#item_search').val(ui.item.label);
                    $('[name="item_id"]').val(ui.item.value);
                    loadItemInfo(ui.item.value);
                    return false;
                }
            });

            $('#item_search').on('input', function() {
                if ($(this).val() === '') {
                    $('[name="item_id"]').val('');
                    $('#item_info_panel').hide();
                }
            });

            $('[name="location_id"]').on('change', function() {
                $('#item_search').val('');
                $('[name="item_id"]').val('');
                $('#item_info_panel').hide();
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
                        remote: "<?= site_url("$controller_name/checkNumeric") ?>"
                    },
                    comment: {
                        required: function() {
                            return $('#reason').val() === 'other';
                        }
                    }
                },

                messages: {
                    item_id: {
                        required: "<?= esc(lang('Inventory_outputs.item_required'), 'js') ?>",
                        min: "<?= esc(lang('Inventory_outputs.item_required'), 'js') ?>"
                    },
                    quantity: {
                        required: "<?= esc(lang('Inventory_outputs.quantity_required'), 'js') ?>",
                        remote: "<?= esc(lang('Inventory_outputs.quantity_number'), 'js') ?>"
                    },
                    comment: {
                        required: "<?= esc(lang('Inventory_outputs.comment_required_other'), 'js') ?>"
                    }
                }
            }, form_support.error));
        });
    </script>

<?php endif; ?>
