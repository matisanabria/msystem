<?php
/**
 * @var object $expenses_info
 * @var array $payment_options
 * @var array $expense_categories
 * @var array $employees
 * @var array $stock_locations
 * @var bool $show_location_select
 * @var bool $can_change_employee
 * @var string $controller_name
 * @var array $config
 */

$is_existing = $expenses_info->expense_id > 0;

// The date shows no seconds; the submitted value (hidden "date") keeps the full format and the original seconds
$date_timestamp   = strtotime($expenses_info->date);
$show_seconds     = str_contains($config['timeformat'], 's');
$display_time_fmt = trim(str_replace(['s', ':s', '.s'], '', $config['timeformat']), ':. ');
$display_format   = $config['dateformat'] . ' ' . ($show_seconds ? $display_time_fmt : $config['timeformat']);

// Number mask: separators of the configured locale
$number_format  = new NumberFormatter($config['number_locale'], NumberFormatter::DECIMAL);
$group_sep      = empty($config['thousands_separator']) ? '' : $number_format->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
$decimal_sep    = $number_format->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
$decimals       = (int) $config['currency_decimals'];
?>

<style>
    .modal-dlg-expenses .modal-footer .btn {
        min-height: 44px;
        min-width: 110px;
    }

    .modal-dlg-expenses .btn:focus-visible,
    #expenses_edit_form .form-control:focus-visible,
    #expenses_edit_form .form-select:focus-visible {
        outline: 3px solid #0d6efd;
        outline-offset: 2px;
    }

    #expenses_edit_form .form-label {
        margin-bottom: 4px;
        font-weight: 600;
    }

    #expenses_edit_form .field-error {
        margin-top: 4px;
        font-size: 13px;
        color: #b02a37;
    }

    #expenses_edit_form #amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    #expenses_edit_form .deleted-block {
        margin-top: 20px;
        padding-top: 12px;
        border-top: 1px solid var(--bs-border-color, #dee2e6);
    }
</style>

<p id="required_fields_message" class="text-body-secondary mb-3"><?= lang('Expenses.required_note') ?></p>

<?= form_open("expenses/save/$expenses_info->expense_id", ['id' => 'expenses_edit_form', 'novalidate' => 'novalidate']) ?>
    <fieldset id="item_basic_info">

        <div class="mb-3">
            <label for="date_display" class="form-label"><?= lang('Expenses.date') ?> <span aria-hidden="true">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><span class="bi bi-calendar3" aria-hidden="true"></span></span>
                <?= form_input([
                    'id'            => 'date_display',
                    'class'         => 'form-control',
                    'value'         => date($display_format, $date_timestamp),
                    'readonly'      => 'readonly',
                    'aria-required' => 'true'
                ]) ?>
            </div>
            <?= form_input(['type' => 'hidden', 'name' => 'date', 'id' => 'date', 'value' => to_datetime($date_timestamp)]) ?>
        </div>

        <?= form_input(['type' => 'hidden', 'name' => 'supplier_id', 'id' => 'supplier_id', 'value' => $expenses_info->supplier_id ?: '']) ?>
        <?= form_input(['type' => 'hidden', 'name' => 'supplier_name', 'id' => 'supplier_name', 'value' => $expenses_info->supplier_name ?? '']) ?>
        <?= form_input(['type' => 'hidden', 'name' => 'supplier_tax_code', 'value' => $expenses_info->supplier_tax_code ?? '']) ?>

        <div class="mb-3">
            <label for="amount" class="form-label"><?= lang('Expenses.amount') ?> <span aria-hidden="true">*</span></label>
            <div class="input-group">
                <?php if (!is_right_side_currency_symbol()): ?>
                    <span class="input-group-text"><b><?= esc($config['currency_symbol']) ?></b></span>
                <?php endif; ?>
                <?= form_input([
                    'name'          => 'amount',
                    'id'            => 'amount',
                    'class'         => 'form-control',
                    'value'         => to_currency_no_money($expenses_info->amount),
                    'inputmode'     => $decimals > 0 ? 'decimal' : 'numeric',
                    'autocomplete'  => 'off',
                    'aria-required' => 'true'
                ]) ?>
                <?php if (is_right_side_currency_symbol()): ?>
                    <span class="input-group-text"><b><?= esc($config['currency_symbol']) ?></b></span>
                <?php endif; ?>
            </div>
        </div>

        <?= form_input(['type' => 'hidden', 'name' => 'tax_amount', 'id' => 'tax_amount', 'value' => to_currency_no_money($expenses_info->tax_amount ?? 0)]) ?>

        <div class="mb-3">
            <label for="payment_type" class="form-label"><?= lang('Expenses.payment') ?></label>
            <?= form_dropdown('payment_type', $payment_options, $expenses_info->payment_type, ['class' => 'form-select', 'id' => 'payment_type']) ?>
        </div>

        <div class="mb-3">
            <label for="employee_id" class="form-label"><?= lang('Expenses.employee') ?></label>
            <?php if (!empty($can_change_employee)): ?>
                <?= form_dropdown('employee_id', $employees, $expenses_info->employee_id, 'id="employee_id" class="form-select"') ?>
            <?php else: ?>
                <?= form_dropdown('employee_id_shown', $employees, $expenses_info->employee_id, 'id="employee_id" class="form-select" disabled') ?>
                <?= form_input(['type' => 'hidden', 'name' => 'employee_id', 'value' => $expenses_info->employee_id]) ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($show_location_select) && !empty($stock_locations)): ?>
            <div class="mb-3">
                <label for="location_id" class="form-label"><?= lang('Expenses.location') ?></label>
                <?= form_dropdown('location_id', $stock_locations, $expenses_info->location_id ?? '', ['id' => 'location_id', 'class' => 'form-select']) ?>
            </div>
        <?php else: ?>
            <?= form_input(['type' => 'hidden', 'name' => 'location_id', 'value' => $expenses_info->location_id ?? array_key_first($stock_locations ?? [])]) ?>
        <?php endif; ?>

        <div class="mb-3">
            <label for="description" class="form-label"><?= lang('Expenses.description') ?></label>
            <?= form_textarea([
                'name'        => 'description',
                'id'          => 'description',
                'class'       => 'form-control',
                'rows'        => 3,
                'placeholder' => lang('Expenses.description_placeholder'),
                'value'       => $expenses_info->description
            ]) ?>
        </div>

        <?php if ($is_existing) { ?>
            <div class="deleted-block">
                <div class="form-check">
                    <?= form_checkbox([
                        'name'             => 'deleted',
                        'id'               => 'deleted',
                        'value'            => 1,
                        'class'            => 'form-check-input',
                        'checked'          => $expenses_info->deleted == 1,
                        'aria-describedby' => 'deleted_help'
                    ]) ?>
                    <label for="deleted" class="form-check-label fw-semibold"><?= lang('Expenses.is_deleted') ?></label>
                </div>
                <div id="deleted_help" class="form-text"><?= lang('Expenses.deleted_help') ?></div>
            </div>
        <?php } ?>

    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        <?= view('partial/datepicker_locale') ?>

        var $form = $('#expenses_edit_form');
        var $amount = $('#amount');
        var GROUP = <?= json_encode($group_sep) ?>;
        var DECIMAL = <?= json_encode($decimal_sep) ?>;
        var DECIMALS = <?= $decimals ?>;
        var SHOW_SECONDS = <?= $show_seconds ? 'true' : 'false' ?>;
        var ORIGINAL_SECONDS = <?= json_encode(date('s', $date_timestamp)) ?>;
        var saved = false;

        // Thousands separator while typing (the submitted text keeps the locale format the server already parses)
        var mask = function(value, caret) {
            var significant = 0;
            for (var i = 0; i < caret; i++) {
                if (/[0-9]/.test(value.charAt(i)) || (DECIMALS > 0 && value.charAt(i) === DECIMAL)) {
                    significant++;
                }
            }

            var int_part = '', dec_part = '', has_decimal = false;
            for (var j = 0; j < value.length; j++) {
                var c = value.charAt(j);
                if (/[0-9]/.test(c)) {
                    if (has_decimal) { dec_part += c; } else { int_part += c; }
                } else if (DECIMALS > 0 && c === DECIMAL && !has_decimal) {
                    has_decimal = true;
                }
            }
            int_part = int_part.replace(/^0+(?=\d)/, '');
            if (GROUP) {
                int_part = int_part.replace(/\B(?=(\d{3})+(?!\d))/g, GROUP);
            }
            dec_part = dec_part.substr(0, DECIMALS);
            var masked = int_part + (has_decimal ? DECIMAL + dec_part : '');

            var position = 0, seen = 0;
            while (position < masked.length && seen < significant) {
                if (/[0-9]/.test(masked.charAt(position)) || (DECIMALS > 0 && masked.charAt(position) === DECIMAL)) {
                    seen++;
                }
                position++;
            }
            return {text: masked, caret: position};
        };

        $amount.on('input', function() {
            var result = mask(this.value, this.selectionStart || 0);
            this.value = result.text;
            this.setSelectionRange(result.caret, result.caret);
        });

        // Select the content on focus so typing replaces it
        $amount.on('focus', function() {
            var el = this;
            setTimeout(function() { el.select(); }, 0);
        }).on('mouseup', function(event) {
            if (document.activeElement !== this) {
                event.preventDefault();
            }
        });

        // Date: picker on the display field (no seconds); the hidden "date" keeps the full value that was always submitted
        var date_input = document.getElementById('date_display');
        var sync_date = function() {
            $('#date').val(date_input.value + (SHOW_SECONDS ? ':' + ORIGINAL_SECONDS : ''));
        };
        new tempusDominus.TempusDominus(date_input, pickerconfig({
            localization: {format: <?= json_encode(dateformat_tempus($config['dateformat']) . ' ' . dateformat_tempus($show_seconds ? $display_time_fmt : $config['timeformat'])) ?>}
        }));
        date_input.addEventListener('change.td', sync_date);
        $(date_input).on('change input blur', sync_date);

        // Enter in the description is a new line, not "Save" (the dialog hotkey listens on keyup)
        $('#description').on('keyup', function(event) {
            if (event.which === 13) {
                event.stopPropagation();
            }
        });

        $form.validate($.extend({}, form_support.error, {
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        saved = true;
                        dialog_support.hide();
                        table_support.handle_submit("<?= esc($controller_name) ?>", response);
                    },
                    error: function() {
                        $('#submit').prop('disabled', false).css('opacity', '');
                    },
                    dataType: 'json'
                });
            },

            // The dialog disables Save right after a submit attempt even when validation failed: give it back
            invalidHandler: function() {
                setTimeout(function() {
                    $('#submit').prop('disabled', false).css('opacity', '');
                }, 0);
            },

            errorElement: 'div',
            errorLabelContainer: null,
            wrapper: null,

            errorPlacement: function(error, element) {
                error.addClass('field-error');
                var $group = element.closest('.input-group');
                error.insertAfter($group.length ? $group : element);
            },

            highlight: function(element) {
                $(element).addClass('is-invalid').attr('aria-invalid', 'true');
            },

            unhighlight: function(element) {
                $(element).removeClass('is-invalid').removeAttr('aria-invalid');
            },

            ignore: '',

            rules: {
                date: {
                    required: true
                },
                amount: {
                    required: true,
                    remote: "<?= "$controller_name/checkNumeric" ?>"
                }
            },

            messages: {
                date: {
                    required: "<?= lang('Expenses.date_required') ?>"

                },
                amount: {
                    required: "<?= lang('Expenses.amount_required') ?>",
                    remote: "<?= lang('Expenses.amount_number') ?>"
                }
            }
        }));

        // Closing (X, Escape, click outside) with unsaved changes asks first
        var snapshot = $form.serialize();
        var dialog = null;
        $.each(BootstrapDialog.dialogs, function(id, candidate) {
            if (candidate.isRealized() && candidate.getModalBody().find('#expenses_edit_form').length) {
                dialog = candidate;
            }
        });
        var declined_at = 0;
        if (dialog) {
            dialog.onHide(function() {
                if (saved || $form.serialize() === snapshot) {
                    return true;
                }
                // Escape fires hide twice (keydown + dialog keyup): don't ask again right after a "no"
                if (Date.now() - declined_at < 600) {
                    return false;
                }
                var discard = confirm(<?= json_encode(lang('Expenses.discard_changes')) ?>);
                if (!discard) {
                    declined_at = Date.now();
                }
                return discard;
            });
        }

        // Start on the amount
        $amount.trigger('focus');
        setTimeout(function() {
            if (document.activeElement !== $amount[0] && !$form.find(':focus').length) {
                $amount.trigger('focus');
            }
        }, 250);
    });
</script>
