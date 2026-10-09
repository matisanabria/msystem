<?php
/**
 * @var object $item_info
 * @var array $categories
 * @var int $selected_category
 * @var bool $standard_item_locked
 * @var bool $item_kit_disabled
 * @var int $allow_temp_item
 * @var array $suppliers
 * @var int $selected_supplier
 * @var bool $use_destination_based_tax
 * @var float $default_tax_1_rate
 * @var float $default_tax_2_rate
 * @var string $tax_category
 * @var int $tax_category_id
 * @var bool $include_hsn
 * @var string $hsn_code
 * @var array $stock_locations
 * @var bool $logo_exists
 * @var string $image_path
 * @var string $selected_low_sell_item
 * @var int $selected_low_sell_item_id
 * @var string $controller_name
 * @var array $config
 */

$is_new = (int) $item_info->item_id === NEW_ENTRY;

// Number symbols for the live price formatting (same locale rules parse_decimals() uses on save)
$number_format = new NumberFormatter($config['number_locale'], NumberFormatter::DECIMAL);
$number_symbols = [
    'grouping' => empty($config['thousands_separator']) ? '' : $number_format->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL),
    'decimal'  => $number_format->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
    'decimals' => (int) $config['currency_decimals'],
];

$price_field = static function (string $name, string $label, $value) use ($config, $number_symbols): string {
    $input = form_input([
        'name'           => $name,
        'id'             => $name,
        'class'          => 'form-control price-input',
        'inputmode'      => $number_symbols['decimals'] > 0 ? 'decimal' : 'numeric',
        'autocomplete'   => 'off',
        'aria-required'  => 'true',
        'value'          => to_currency_no_money($value)
    ]);
    $symbol = '<span class="input-group-text"><b>' . esc($config['currency_symbol']) . '</b></span>';
    $group = is_right_side_currency_symbol() ? $input . $symbol : $symbol . $input;

    return '<div><label class="form-label" for="' . $name . '">' . $label . ' <span class="req" aria-hidden="true">*</span></label>'
        . '<div class="input-group">' . $group . '</div></div>';
};
?>

<p class="item-required-note text-body-secondary small mb-3"><?= lang('Items.required_note') ?></p>

<?= form_open("items/save/$item_info->item_id", ['id' => 'item_form', 'enctype' => 'multipart/form-data', 'class' => 'item-form']) ?>

    <fieldset class="item-section">
        <legend><?= lang('Items.section_item') ?></legend>

        <?php if (!empty($show_location_select) && count($allowed_locations) > 1): ?>
            <div class="mb-3">
                <?= form_label(lang('Common.location'), 'location_id', ['class' => 'form-label']) ?>
                <?= form_dropdown('location_id', $allowed_locations, (string) $item_location_id, ['id' => 'location_id', 'class' => 'form-select']) ?>
            </div>
        <?php else: ?>
            <?= form_hidden('location_id', (string) ($item_location_id ?? (string) array_key_first($allowed_locations ?? []))) ?>
        <?php endif; ?>

        <div class="mb-3">
            <?= form_label(lang('Items.item_number'), 'item_number', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'         => 'item_number',
                'id'           => 'item_number',
                'class'        => 'form-control',
                'autocomplete' => 'off',
                'value'        => $item_info->item_number
            ]) ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="name"><?= lang('Items.name') ?> <span class="req" aria-hidden="true">*</span></label>
            <?= form_input([
                'name'          => 'name',
                'id'            => 'name',
                'class'         => 'form-control',
                'aria-required' => 'true',
                'value'         => $item_info->name
            ]) ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="<?= $config['category_dropdown'] ? 'category_select' : 'category' ?>"><?= lang('Items.category') ?> <span class="req" aria-hidden="true">*</span></label>
            <?php
            if ($config['category_dropdown']) {
                echo form_dropdown('category', $categories, $selected_category, ['id' => 'category_select', 'class' => 'form-select', 'aria-required' => 'true']);
            } else {
                echo form_input([
                    'name'          => 'category',
                    'id'            => 'category',
                    'class'         => 'form-control',
                    'aria-required' => 'true',
                    'value'         => $item_info->category
                ]);
            }
            ?>
        </div>

        <div class="mb-3">
            <?= form_label(lang('Items.supplier'), 'supplier_id', ['class' => 'form-label']) ?>
            <?= form_dropdown('supplier_id', $suppliers, $selected_supplier, ['id' => 'supplier_id', 'class' => 'form-select']) ?>
        </div>

        <?php if ($include_hsn): ?>
            <div class="mb-3">
                <?= form_label(lang('Items.hsn_code'), 'hsn_code', ['class' => 'form-label']) ?>
                <?= form_input([
                    'name'  => 'hsn_code',
                    'id'    => 'hsn_code',
                    'class' => 'form-control',
                    'value' => $hsn_code
                ]) ?>
            </div>
        <?php endif; ?>
    </fieldset>

    <fieldset class="item-section">
        <legend><?= lang('Items.section_prices') ?></legend>
        <div class="price-grid">
            <?= $price_field('cost_price', lang('Items.cost_price'), $item_info->cost_price) ?>
            <?= $price_field('unit_price', lang('Items.unit_price'), $item_info->unit_price) ?>
            <?= $price_field('price_wholesale', lang('Items.price_wholesale'), $item_info->price_wholesale ?? $item_info->unit_price) ?>
            <?= $price_field('price_reseller', lang('Items.price_reseller'), $item_info->price_reseller ?? $item_info->unit_price) ?>
        </div>
        <div class="field-warning d-none" id="price-warning" role="status"><?= lang('Items.price_below_cost') ?></div>
    </fieldset>

    <fieldset class="item-section">
        <legend><?= lang('Items.section_stock') ?></legend>

        <?php foreach ($stock_locations as $key => $location_detail): ?>
            <div class="mb-3">
                <label class="form-label" for="quantity_<?= $key ?>"><?= lang('Items.current_quantity') . (count($stock_locations) > 1 ? ' ' . esc($location_detail['location_name']) : '') ?> <span class="req" aria-hidden="true">*</span></label>
                <?= form_input([
                    'name'          => "quantity_$key",
                    'id'            => "quantity_$key",
                    'class'         => 'required quantity form-control qty-input',
                    'inputmode'     => 'decimal',
                    'autocomplete'  => 'off',
                    'aria-required' => 'true',
                    'value'         => !$is_new ? to_quantity_decimals($location_detail['quantity']) : to_quantity_decimals(0)
                ]) ?>
            </div>
        <?php endforeach; ?>

        <?php if (!$is_new && model(\App\Models\Employee::class)->has_grant('receivings', session()->get('person_id'))): ?>
            <div class="mb-3">
                <p class="form-text mb-1"><?= lang('Items.receive_stock_hint') ?></p>
                <a href="<?= base_url('receivings') ?>" class="btn btn-outline-secondary btn-sm">
                    <span class="bi bi-box-arrow-in-down" aria-hidden="true"></span> <?= lang('Items.receive_stock') ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="receiving_quantity"><?= lang('Items.receiving_quantity') ?> <span class="req" aria-hidden="true">*</span></label>
            <?= form_input([
                'name'          => 'receiving_quantity',
                'id'            => 'receiving_quantity',
                'class'         => 'required form-control qty-input',
                'inputmode'     => 'decimal',
                'autocomplete'  => 'off',
                'aria-required' => 'true',
                'value'         => !$is_new ? to_quantity_decimals($item_info->receiving_quantity) : to_quantity_decimals(0)
            ]) ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="reorder_level"><?= lang('Items.reorder_level_alert') ?> <span class="req" aria-hidden="true">*</span></label>
            <?= form_input([
                'name'             => 'reorder_level',
                'id'               => 'reorder_level',
                'class'            => 'form-control qty-input',
                'inputmode'        => 'decimal',
                'autocomplete'     => 'off',
                'aria-required'    => 'true',
                'aria-describedby' => 'reorder_level_help',
                'value'            => !$is_new ? to_quantity_decimals($item_info->reorder_level) : to_quantity_decimals(0)
            ]) ?>
            <div class="form-text" id="reorder_level_help"><?= lang('Items.reorder_level_help') ?></div>
        </div>

        <?php if ($config['multi_pack_enabled'] == '1'): ?>
            <div class="mb-3">
                <?= form_label(lang('Items.qty_per_pack'), 'qty_per_pack', ['class' => 'form-label']) ?>
                <?= form_input([
                    'name'  => 'qty_per_pack',
                    'id'    => 'qty_per_pack',
                    'class' => 'form-control qty-input',
                    'value' => !$is_new ? to_quantity_decimals($item_info->qty_per_pack) : to_quantity_decimals(0)
                ]) ?>
            </div>
            <div class="mb-3">
                <?= form_label(lang('Items.pack_name'), 'pack_name', ['class' => 'form-label']) ?>
                <?= form_input([
                    'name'  => 'pack_name',
                    'id'    => 'pack_name',
                    'class' => 'form-control',
                    'value' => $item_info->pack_name
                ]) ?>
            </div>
            <div class="mb-3">
                <?= form_label(lang('Items.low_sell_item'), 'low_sell_item_name', ['class' => 'form-label']) ?>
                <?= form_input([
                    'name'  => 'low_sell_item_name',
                    'id'    => 'low_sell_item_name',
                    'class' => 'form-control',
                    'value' => $selected_low_sell_item
                ]) ?>
                <?= form_hidden('low_sell_item_id', $selected_low_sell_item_id) ?>
            </div>
        <?php endif; ?>
    </fieldset>

    <fieldset class="item-section">
        <legend><?= lang('Items.section_description') ?></legend>

        <div class="mb-3">
            <?= form_label(lang('Items.description'), 'description', ['class' => 'form-label']) ?>
            <?= form_textarea([
                'name'  => 'description',
                'id'    => 'description',
                'class' => 'form-control',
                'rows'  => 3,
                'value' => $item_info->description
            ]) ?>
        </div>

        <div class="mb-3">
            <span class="form-label d-block" id="item_photo_label"><?= lang('Items.item_photo') ?></span>
            <div class="image-input" data-image-input>
                <div class="image-input-preview item-photo-preview mb-2 <?= $logo_exists ? '' : 'd-none' ?>">
                    <img alt="<?= esc(lang('Items.item_photo'), 'attr') ?>" src="<?= $image_path ?>">
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="choose_photo" aria-describedby="item_photo_label">
                        <span class="bi bi-image" aria-hidden="true"></span>
                        <span class="image-input-label" data-select="<?= esc(lang('Items.choose_photo'), 'attr') ?>" data-change="<?= esc(lang('Items.choose_photo'), 'attr') ?>"><?= lang('Items.choose_photo') ?></span>
                    </button>
                    <input type="file" name="items_image" id="items_image" accept="image/*" class="d-none" tabindex="-1" aria-labelledby="item_photo_label">
                    <button type="button" class="btn btn-outline-secondary image-input-remove <?= $logo_exists ? '' : 'd-none' ?>"><?= lang('Items.remove_image') ?></button>
                </div>
            </div>
        </div>
    </fieldset>

    <?php if (!$is_new): ?>
        <div class="item-deleted">
            <div class="form-check">
                <?= form_checkbox([
                    'name'             => 'is_deleted',
                    'id'               => 'is_deleted',
                    'class'            => 'form-check-input',
                    'value'            => 1,
                    'checked'          => $item_info->deleted == 1,
                    'aria-describedby' => 'is_deleted_help'
                ]) ?>
                <label class="form-check-label" for="is_deleted"><?= lang('Items.is_deleted') ?></label>
            </div>
            <div class="form-text" id="is_deleted_help"><?= lang('Items.is_deleted_help') ?></div>
        </div>
    <?php endif; ?>

<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        var NUM = <?= json_encode($number_symbols) ?>;
        var $form = $('#item_form');
        var $modal = $form.closest('.modal');
        var allow_close = false;
        var snapshot = '';

        $modal.closest('.bootstrap-dialog').addClass('items-dialog');

        var form_state = function() {
            var file = $form.find('input[name="items_image"]')[0];
            return $form.serialize() + '&file=' + (file && file.files && file.files[0] ? file.files[0].name : '');
        };

        var set_sending = function(sending) {
            $('#submit, #new').prop('disabled', sending).css('opacity', '');
        };

        $('#new').click(function() {
            $form.submit();
        });

        // --- Initial focus: barcode field; Enter there moves to Name instead of submitting
        var focus_barcode = function() {
            var $field = $('#item_number');
            $field.trigger('focus');
            setTimeout(function() { !$field.is(':focus') && $field.trigger('focus'); }, 350);
        };
        var barcode_enter_down = false;
        $('#item_number').on('keydown', function(event) {
            if (event.which === 13) {
                event.preventDefault();
                barcode_enter_down = true;
            }
        }).on('keyup', function(event) {
            if (event.which === 13 && barcode_enter_down) {
                barcode_enter_down = false;
                event.stopPropagation(); // keeps the dialog's Enter hotkey from firing
                $('#name').trigger('focus');
            }
        });
        focus_barcode();

        // --- Prices: thousands separator while typing, right aligned, selected on focus
        var format_price = function(raw) {
            var dec_at = NUM.decimals > 0 ? raw.indexOf(NUM.decimal) : -1;
            var int_part = (dec_at >= 0 ? raw.slice(0, dec_at) : raw).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
            var dec_part = dec_at >= 0 ? raw.slice(dec_at + NUM.decimal.length).replace(/\D/g, '').slice(0, NUM.decimals) : null;
            if (NUM.grouping) {
                int_part = int_part.replace(/\B(?=(\d{3})+(?!\d))/g, NUM.grouping);
            }
            return dec_part === null ? int_part : (int_part || '0') + NUM.decimal + dec_part;
        };

        var significant_before = function(text, pos) {
            var before = text.slice(0, pos);
            return (before.match(/\d/g) || []).length + (NUM.decimals > 0 && before.indexOf(NUM.decimal) >= 0 ? 1 : 0);
        };

        var caret_for = function(text, count) {
            if (count === 0) { return 0; }
            var seen = 0, decimal_seen = false;
            for (var i = 0; i < text.length; i++) {
                if (/\d/.test(text[i])) {
                    seen++;
                } else if (NUM.decimals > 0 && !decimal_seen && text.substr(i, NUM.decimal.length) === NUM.decimal) {
                    decimal_seen = true;
                    seen++;
                }
                if (seen === count) { return i + 1; }
            }
            return text.length;
        };

        var to_number = function(text) {
            var clean = (text || '').split(NUM.grouping || '\u0000').join('').replace(NUM.decimal, '.');
            return parseFloat(clean);
        };

        var check_price_warning = function() {
            var cost = to_number($('#cost_price').val());
            var sale = to_number($('#unit_price').val());
            $('#price-warning').toggleClass('d-none', !(isFinite(cost) && isFinite(sale) && sale < cost));
        };

        $('.price-input').on('input', function() {
            var el = this;
            var count = significant_before(el.value, el.selectionStart || 0);
            el.value = format_price(el.value);
            var pos = caret_for(el.value, count);
            el.setSelectionRange(pos, pos);
            check_price_warning();
        });

        $('.price-input, .qty-input').on('focus', function() {
            var el = this;
            setTimeout(function() { el.select(); }, 0);
        }).on('mouseup', function(event) {
            if (this.dataset.justFocused) {
                event.preventDefault();
            }
            delete this.dataset.justFocused;
        }).on('mousedown', function() {
            if (document.activeElement !== this) {
                this.dataset.justFocused = '1';
            }
        });
        check_price_warning();

        var fill_low_sell_value = function(event, ui) {
            event.preventDefault();
            $("input[name='low_sell_item_id']").val(ui.item.value);
            $("input[name='low_sell_item_name']").val(ui.item.label);
        };

        $('#low_sell_item_name').autocomplete({
            source: "<?= 'items/suggestLowSell' ?>",
            minChars: 0,
            delay: 15,
            cacheLength: 1,
            appendTo: '.modal-content',
            select: fill_low_sell_value,
            focus: fill_low_sell_value
        });

        $('#category').autocomplete({
            source: "<?= 'items/suggestCategory' ?>",
            delay: 10,
            appendTo: '.modal-content'
        });

        // --- Photo: the visible button opens the (hidden) file input so it is reachable by keyboard
        $('#choose_photo').on('click', function() {
            $('input[name="items_image"]').trigger('click');
        });

        $('.image-input-remove').click(function() {
            $.ajax({
                type: 'GET',
                url: '<?= "$controller_name/removeLogo/$item_info->item_id" ?>',
                dataType: 'json'
            })
        });

        // Auto-compress item images client-side so they fit the configured
        // max dimensions/size without the user having to resize manually.
        var IMAGE_MAX_WIDTH  = <?= (int) $config['image_max_width'] ?>;
        var IMAGE_MAX_HEIGHT = <?= (int) $config['image_max_height'] ?>;
        var IMAGE_MAX_BYTES  = <?= (int) $config['image_max_size'] ?> * 1024;

        function compress_image_file(file) {
            return new Promise(function(resolve) {
                if (!file || file.type.indexOf('image/') !== 0) {
                    resolve(file);
                    return;
                }

                var img = new Image();
                img.onload = function() {
                    var scale = Math.min(1, IMAGE_MAX_WIDTH / img.width, IMAGE_MAX_HEIGHT / img.height);
                    var canvas = document.createElement('canvas');
                    canvas.width = Math.round(img.width * scale);
                    canvas.height = Math.round(img.height * scale);
                    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(img.src);

                    var quality = 0.92;

                    var try_export = function() {
                        canvas.toBlob(function(blob) {
                            if (!blob) {
                                resolve(file);
                                return;
                            }
                            if (blob.size > IMAGE_MAX_BYTES && quality > 0.3) {
                                quality -= 0.1;
                                try_export();
                                return;
                            }
                            var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                            resolve(new File([blob], name, { type: 'image/jpeg' }));
                        }, 'image/jpeg', quality);
                    };
                    try_export();
                };
                img.onerror = function() {
                    resolve(file);
                };
                img.src = URL.createObjectURL(file);
            });
        }

        $('input[name="items_image"]').on('change', function() {
            var input = this;
            var file  = input.files && input.files[0];
            if (!file) { return; }

            compress_image_file(file).then(function(compressed_file) {
                if (compressed_file === file) { return; }
                var transfer = new DataTransfer();
                transfer.items.add(compressed_file);
                input.files = transfer.files;
            });
        });

        $.validator.addMethod('valid_chars', function(value, element) {
            return value.match(/(\||_)/g) == null;
        }, "<?= lang('Attributes.attribute_value_invalid_chars') ?>");

        var init_validation = function() {
            $form.validate({
                onkeyup: false,
                errorElement: 'div',
                errorClass: 'field-error',
                errorPlacement: function(error, element) {
                    var id = (element.attr('id') || element.attr('name')) + '-error';
                    var $anchor = element.closest('.input-group').length ? element.closest('.input-group') : element;
                    error.attr('id', id).insertAfter($anchor);
                    var described = (element.attr('aria-describedby') || '').split(' ').filter(function(part) { return part && part !== id; });
                    described.push(id);
                    element.attr('aria-describedby', described.join(' '));
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid').attr('aria-invalid', 'true');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid').removeAttr('aria-invalid');
                },
                submitHandler: function(form, event) { // Event is not used as a parameter here
                    set_sending(true);
                    $(form).ajaxSubmit({
                        success: function(response) {
                            set_sending(false);
                            let stay_open = dialog_support.clicked_id() != 'submit';
                            if (stay_open) {
                                // Set action of item_form to url without item id, so a new one can be created
                                $('#item_form').attr('action', "<?= 'items/save/' ?>");
                                // Use a whitelist of fields to minimize unintended side effects
                                $(':text, :password, :file, #description, #item_form').not('.quantity, #reorder_level, #tax_name_1, #receiving_quantity, ' +
                                    '#tax_percent_name_1, #category, #reference_number, #name, #cost_price, #unit_price, #price_wholesale, #price_reseller, #taxed_cost_price, #taxed_unit_price, #definition_name, [name^="attribute_links"]').val('');
                                // De-select any checkboxes, radios and drop-down menus
                                $(':input', '#item_form').removeAttr('checked').removeAttr('selected');
                                // Clear the photo preview only (the remove button would delete the saved item's photo)
                                $('.image-input-preview, .image-input-remove').addClass('d-none');
                                $('.image-input-preview img').attr('src', '');
                                snapshot = form_state();
                                focus_barcode();
                            } else {
                                allow_close = true;
                                dialog_support.hide();
                            }
                            table_support.handle_submit('<?= 'items' ?>', response, stay_open);
                            init_validation();
                        },
                        error: function() {
                            set_sending(false);
                            $.notify("<?= esc(lang('Items.error_adding_updating'), 'js') ?>", { type: 'danger' });
                        },
                        dataType: 'json'
                    });
                },

                rules: {
                    name: 'required',
                    category: 'required',
                    item_number: {
                        required: false,
                        remote: {
                            url: "<?= esc("$controller_name/checkItemNumber") ?>",
                            type: 'POST',
                            data: {
                                'item_id': "<?= $item_info->item_id ?>",
                                'location_id': function() { return $('[name="location_id"]').val(); }
                            }
                        }
                    },
                    cost_price: {
                        required: true,
                        remote: "<?= esc("$controller_name/checkNumeric") ?>"
                    },
                    unit_price: {
                        required: true,
                        remote: "<?= esc("$controller_name/checkNumeric") ?>"
                    },
                    price_wholesale: {
                        required: true,
                        remote: "<?= esc("$controller_name/checkNumeric") ?>"
                    },
                    price_reseller: {
                        required: true,
                        remote: "<?= esc("$controller_name/checkNumeric") ?>"
                    },
                    <?php foreach (array_unique(array_merge(array_keys($stock_locations), array_keys($allowed_locations ?? []))) as $key) { ?>
                        <?= 'quantity_' . $key ?>: {
                            required: true,
                            remote: "<?= esc("$controller_name/checkNumeric") ?>"
                        },
                    <?php } ?>
                    receiving_quantity: {
                        required: true,
                        remote: "<?= esc("$controller_name/checkNumeric") ?>"
                    },
                    reorder_level: {
                        required: true,
                        remote: "<?= esc("$controller_name/checkNumeric") ?>"
                    },
                    tax_percent: {
                        required: false,
                        remote: "<?= esc("$controller_name/checkNumeric") ?>"
                    }
                },

                messages: {
                    name: "<?= lang('Items.name_required') ?>",
                    item_number: "<?= lang('Items.item_number_duplicate') ?>",
                    category: "<?= lang('Items.category_required') ?>",
                    cost_price: {
                        required: "<?= lang('Items.cost_price_required') ?>",
                        number: "<?= lang('Items.cost_price_number') ?>",
                        remote: "<?= lang('Items.cost_price_number') ?>"
                    },
                    unit_price: {
                        required: "<?= lang('Items.unit_price_required') ?>",
                        number: "<?= lang('Items.unit_price_number') ?>",
                        remote: "<?= lang('Items.unit_price_number') ?>"
                    },
                    price_wholesale: {
                        required: "<?= lang('Items.price_wholesale_required') ?>",
                        number: "<?= lang('Items.price_wholesale_number') ?>",
                        remote: "<?= lang('Items.price_wholesale_number') ?>"
                    },
                    price_reseller: {
                        required: "<?= lang('Items.price_reseller_required') ?>",
                        number: "<?= lang('Items.price_reseller_number') ?>",
                        remote: "<?= lang('Items.price_reseller_number') ?>"
                    },
                    <?php foreach (array_unique(array_merge(array_keys($stock_locations), array_keys($allowed_locations ?? []))) as $key) { ?>
                        <?= esc("quantity_$key", 'js') ?>: {
                            required: "<?= lang('Items.quantity_required') ?>",
                            number: "<?= lang('Items.quantity_number') ?>",
                            remote: "<?= lang('Items.quantity_number') ?>"
                        },
                    <?php } ?>
                    receiving_quantity: {
                        required: "<?= lang('Items.quantity_required') ?>",
                        number: "<?= lang('Items.quantity_number') ?>",
                        remote: "<?= lang('Items.quantity_number') ?>"
                    },
                    reorder_level: {
                        required: "<?= lang('Items.reorder_level_required') ?>",
                        number: "<?= lang('Items.reorder_level_number') ?>",
                        remote: "<?= lang('Items.reorder_level_number') ?>"
                    },
                    tax_percent: {
                        number: "<?= lang('Items.tax_percent_number') ?>"
                    }
                }
            });
        };

        init_validation();

        // New item: the stock field is named after the chosen branch (quantity_<location_id>), which is what
        // Items::postSave reads. Validation rules exist for every allowed branch, so they follow the rename.
        $('#location_id').on('change', function() {
            var $quantity = $form.find('input.qty-input[name^="quantity_"]').first();
            if (!$quantity.length) {
                return;
            }
            var field_name = 'quantity_' + $(this).val();
            $quantity.prop('name', field_name).prop('id', field_name);
            $form.find('label[for^="quantity_"]').attr('for', field_name);
            $quantity.removeClass('is-invalid').removeAttr('aria-invalid').valid();
        });

        // --- Unsaved changes: closing the dialog (X, Escape, click outside) asks before discarding
        snapshot = form_state();
        var show_discard_bar = function() {
            if ($('#discard-bar').length) {
                $('#discard-bar .btn-keep').trigger('focus');
                return;
            }
            var $bar = $(
                '<div class="item-discard-bar border border-2 border-warning rounded p-3 bg-body-tertiary" id="discard-bar" role="alertdialog" aria-labelledby="discard-text">' +
                    '<p class="mb-2 fw-semibold" id="discard-text"></p>' +
                    '<div class="d-flex gap-2">' +
                        '<button type="button" class="btn btn-danger btn-discard"></button>' +
                        '<button type="button" class="btn btn-primary btn-keep"></button>' +
                    '</div>' +
                '</div>'
            );
            $bar.find('#discard-text').text("<?= esc(lang('Items.discard_changes'), 'js') ?>");
            $bar.find('.btn-discard').text("<?= esc(lang('Items.discard'), 'js') ?>").on('click', function() {
                allow_close = true;
                bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            });
            $bar.find('.btn-keep').text("<?= esc(lang('Items.keep_editing'), 'js') ?>").on('click', function() {
                $bar.remove();
                $('#item_number').trigger('focus');
            });
            $form.before($bar);
            $bar[0].scrollIntoView({block: 'nearest'});
            $bar.find('.btn-keep').trigger('focus');
        };

        $modal.on('hide.bs.modal', function(event) {
            if (!allow_close && form_state() !== snapshot) {
                event.preventDefault();
                show_discard_bar();
            }
        });
    });
</script>
