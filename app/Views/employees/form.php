<?php
/**
 * @var string $controller_name
 * @var object $person_info
 * @var array $all_modules
 * @var array $all_subpermissions
 * @var int $employee_id
 * @var array $branches
 * @var int[] $branch_access
 * @var bool $is_self
 * @var int[] $protected_locations
 * @var string[] $protected_permissions
 */

use App\Models\Module;

$is_new = (int) $employee_id === NEW_ENTRY;

// Modules offered, grouped like the home grid (leftovers go to the last group)
$modules_by_id = [];
foreach ($all_modules as $module) {
    if (!in_array($module->module_id, Module::EMPLOYEE_FORM_HIDDEN, true)) {
        $modules_by_id[$module->module_id] = $module;
    }
}
$groups = [];
$placed = [];
foreach (Module::EMPLOYEE_FORM_GROUPS as $group_key => $module_ids) {
    foreach ($module_ids as $module_id) {
        if (isset($modules_by_id[$module_id])) {
            $groups[$group_key][] = $modules_by_id[$module_id];
            $placed[$module_id] = true;
        }
    }
}
foreach ($modules_by_id as $module_id => $module) {
    if (!isset($placed[$module_id])) {
        $groups['group_admin'][] = $module;
    }
}

// Sub-permissions per module (location-scoped ones are branch access, handled in the Acceso tab)
$subs_by_module = [];
foreach ($all_subpermissions as $permission) {
    if (in_array($permission->permission_id, Module::EMPLOYEE_FORM_HIDDEN_SUBPERMISSIONS, true) || !empty($permission->location_id)) {
        continue;
    }
    $exploded = explode('_', $permission->permission_id, 2);
    if (!isset($exploded[1])) {
        continue;
    }
    $lang_key = ucfirst($permission->module_id) . '.' . $exploded[1];
    $label = lang($lang_key);
    $label = ($label == $lang_key) ? ucwords(str_replace('_', ' ', $exploded[1])) : $label;
    if ($label !== '') {
        $permission->label = $label;
        $subs_by_module[$permission->module_id][] = $permission;
    }
}

$field_row = static fn (string $name, string $label, string $value, bool $required = false, array $extra = []): string =>
    '<div class="mb-3"><label class="form-label" for="' . $name . '">' . $label . ($required ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>'
    . form_input(array_merge(['name' => $name, 'id' => $name, 'class' => 'form-control', 'value' => $value] + ($required ? ['aria-required' => 'true'] : []), $extra))
    . '</div>';

$eye_icons = '<svg class="icon-show bi" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>'
    . '<svg class="icon-hide bi d-none" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474z"/><path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12z"/></svg>';
?>

<p class="employee-required-note text-body-secondary small mb-2"><?= lang('Employees.required_note') ?></p>

<?= form_open("$controller_name/save/$person_info->person_id", ['id' => 'employee_form', 'class' => 'employee-form', 'novalidate' => 'novalidate']) ?>

    <ul class="nav nav-tabs nav-justified employee-tabs" id="employee_tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-data" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#employee_basic_info" aria-controls="employee_basic_info" aria-selected="true"><?= lang('Employees.tab_data') ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-access" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#employee_login_info" aria-controls="employee_login_info" aria-selected="false"><?= lang('Employees.tab_access') ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-permissions" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#employee_permission_info" aria-controls="employee_permission_info" aria-selected="false"><?= lang('Employees.tab_permissions') ?></button>
        </li>
    </ul>

    <div class="tab-content pt-3">
        <!-- ============ Datos ============ -->
        <div class="tab-pane fade show active" id="employee_basic_info" role="tabpanel" aria-labelledby="tab-data" tabindex="0">
            <?= $field_row('first_name', lang('Common.first_name'), (string) $person_info->first_name, true) ?>
            <?= $field_row('last_name', lang('Common.last_name'), (string) $person_info->last_name, true) ?>
            <?= $field_row('phone_number', lang('Common.phone_number'), (string) $person_info->phone_number, false, ['inputmode' => 'tel', 'autocomplete' => 'off']) ?>

            <details class="employee-more">
                <summary><?= lang('Employees.more_data') ?></summary>
                <div class="pt-3">
                    <?= $field_row('email', lang('Common.email'), (string) $person_info->email, false, ['type' => 'email', 'autocomplete' => 'off']) ?>
                    <?= $field_row('address_1', lang('Common.address_1'), (string) $person_info->address_1) ?>
                    <?= $field_row('city', lang('Common.city'), (string) $person_info->city) ?>
                    <div class="mb-3">
                        <?= form_label(lang('Common.identification_type'), 'identification_type', ['class' => 'form-label']) ?>
                        <?= form_dropdown('identification_type', [
                            ''          => lang('Common.select_id_type'),
                            'CI'        => lang('Common.id_type_ci'),
                            'RUC'       => lang('Common.id_type_ruc'),
                            'DNI'       => lang('Common.id_type_dni'),
                            'PASAPORTE' => lang('Common.id_type_passport'),
                        ], $person_info->identification_type ?? '', ['id' => 'identification_type', 'class' => 'form-select']) ?>
                    </div>
                    <?= $field_row('identification', lang('Common.identification'), (string) ($person_info->identification ?? '')) ?>
                    <?= $field_row('country', lang('Common.country'), (string) $person_info->country) ?>
                    <div class="mb-3">
                        <?= form_label(lang('Common.comments'), 'comments', ['class' => 'form-label']) ?>
                        <?= form_textarea(['name' => 'comments', 'id' => 'comments', 'class' => 'form-control', 'rows' => 3, 'value' => $person_info->comments]) ?>
                    </div>
                </div>
            </details>
        </div>

        <!-- ============ Acceso ============ -->
        <div class="tab-pane fade" id="employee_login_info" role="tabpanel" aria-labelledby="tab-access" tabindex="0">
            <?= $field_row('username', lang('Employees.username'), (string) $person_info->username, true, ['autocomplete' => 'off', 'autocapitalize' => 'none', 'spellcheck' => 'false']) ?>

            <?php foreach ([['password', lang('Employees.password')], ['repeat_password', lang('Employees.repeat_password')]] as [$pw_name, $pw_label]): ?>
                <div class="mb-3">
                    <label class="form-label" for="<?= $pw_name ?>"><?= $pw_label ?><?= $is_new ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
                    <div class="input-group">
                        <?= form_password([
                            'name'         => $pw_name,
                            'id'           => $pw_name,
                            'class'        => 'form-control',
                            'autocomplete' => 'new-password',
                        ] + ($is_new ? ['aria-required' => 'true'] : ['aria-describedby' => 'password_blank_help'])) ?>
                        <button type="button" class="btn btn-outline-secondary toggle-secret" data-target="#<?= $pw_name ?>" aria-label="<?= esc(lang('Employees.show_password'), 'attr') ?>" aria-pressed="false"><?= $eye_icons ?></button>
                    </div>
                    <?php if (!$is_new && $pw_name === 'password'): ?>
                        <div class="form-text" id="password_blank_help"><?= lang('Employees.password_leave_blank') ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="mb-4">
                <label class="form-label" for="pin"><?= lang('Employees.pin') ?></label>
                <div class="input-group">
                    <?= form_input([
                        'name'             => 'pin',
                        'id'               => 'pin',
                        'type'             => 'password',
                        'class'            => 'form-control',
                        'value'            => $person_info->pin ?? '',
                        'maxlength'        => '4',
                        'inputmode'        => 'numeric',
                        'autocomplete'     => 'off',
                        'placeholder'      => '••••',
                        'aria-describedby' => 'pin_help',
                    ]) ?>
                    <button type="button" class="btn btn-outline-secondary toggle-secret" data-target="#pin" aria-label="<?= esc(lang('Employees.show_pin'), 'attr') ?>" aria-pressed="false"><?= $eye_icons ?></button>
                    <button type="button" class="btn btn-outline-secondary" id="generate_pin"><span class="bi bi-dice-5" aria-hidden="true"></span> <?= lang('Employees.pin_generate') ?></button>
                </div>
                <div class="form-text" id="pin_help"><?= lang('Employees.pin_help_new') ?></div>
            </div>

            <fieldset class="employee-branches" aria-describedby="branches_help">
                <legend><?= lang('Employees.branches_title') ?></legend>
                <?php if (empty($branches)): ?>
                    <p class="text-body-secondary"><?= lang('Employees.branches_none') ?></p>
                <?php endif; ?>
                <input type="hidden" name="branch_access_sent" value="1">
                <?php foreach ($branches as $branch):
                    $location_id = (int) $branch['location_id'];
                    $checked = in_array($location_id, $branch_access, true);
                    $locked = $is_self && in_array($location_id, $protected_locations, true);
                    ?>
                    <div class="form-check form-switch branch-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="branch_access[]" value="<?= $location_id ?>" id="branch_<?= $location_id ?>"
                            <?= ($checked || $locked) ? 'checked' : '' ?> <?= $locked ? 'disabled aria-describedby="branch_locked_note"' : '' ?>>
                        <label class="form-check-label" for="branch_<?= $location_id ?>"><?= esc($branch['location_name']) ?></label>
                        <?php if ($locked): ?>
                            <input type="hidden" name="branch_access[]" value="<?= $location_id ?>">
                            <span class="form-text" id="branch_locked_note"><?= lang('Employees.branch_session_note') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="form-text" id="branches_help"><?= lang('Employees.branches_help') ?></div>
                <div class="field-error" id="branch_access-error" role="alert"></div>
            </fieldset>
        </div>

        <!-- ============ Permisos ============ -->
        <div class="tab-pane fade" id="employee_permission_info" role="tabpanel" aria-labelledby="tab-permissions" tabindex="0">
            <fieldset class="perm-profile mb-3">
                <legend class="form-label"><?= lang('Employees.profile_label') ?></legend>
                <div class="segmented perm-profile-options" role="radiogroup" aria-label="<?= esc(lang('Employees.profile_label'), 'attr') ?>">
                    <?php foreach (['cashier' => 'profile_cashier', 'admin' => 'profile_admin', 'custom' => 'profile_custom'] as $profile_key => $profile_lang): ?>
                        <label class="segmented-option" for="profile_<?= $profile_key ?>">
                            <input type="radio" class="visually-hidden" name="permission_profile" id="profile_<?= $profile_key ?>" value="<?= $profile_key ?>">
                            <?= lang("Employees.$profile_lang") ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div class="field-error" id="permissions-error" role="alert"></div>

            <?php foreach ($groups as $group_key => $group_modules): ?>
                <section class="perm-group" data-group="<?= $group_key ?>" aria-labelledby="group_title_<?= $group_key ?>">
                    <div class="perm-group-head">
                        <h3 class="perm-group-title" id="group_title_<?= $group_key ?>"><?= lang("Module.$group_key") ?></h3>
                        <button type="button" class="btn btn-outline-secondary btn-sm perm-group-toggle" data-mark="<?= esc(lang('Employees.mark_all'), 'attr') ?>" data-unmark="<?= esc(lang('Employees.unmark_all'), 'attr') ?>"><?= lang('Employees.mark_all') ?></button>
                    </div>
                    <?php foreach ($group_modules as $module):
                        $module_id = $module->module_id;
                        $locked = $is_self && in_array($module_id, $protected_permissions, true);
                        $menu_group = ($module->grant == 1)
                            ? $module->menu_group
                            : (in_array($module_id, Module::OFFICE_MODULES, true) ? 'office' : 'home');
                        $subs = $subs_by_module[$module_id] ?? [];
                        ?>
                        <div class="perm-module">
                            <div class="form-check">
                                <input class="form-check-input perm-module-check" type="checkbox" name="grant_<?= $module_id ?>" value="<?= $module_id ?>" id="grant_<?= $module_id ?>"
                                    <?= ($module->grant == 1 || $locked) ? 'checked' : '' ?> <?= $locked ? 'disabled aria-describedby="perm_locked_' . $module_id . '"' : '' ?>>
                                <?php if ($locked): ?><input type="hidden" name="grant_<?= $module_id ?>" value="<?= $module_id ?>"><?php endif; ?>
                                <?= form_hidden("menu_group_$module_id", $menu_group) ?>
                                <label class="form-check-label perm-name" for="grant_<?= $module_id ?>"><?= lang("Module.$module_id") ?></label>
                                <div class="perm-desc"><?= lang("Module.{$module_id}_desc") ?></div>
                                <?php if ($locked): ?><div class="perm-desc fw-semibold" id="perm_locked_<?= $module_id ?>"><?= lang('Employees.self_permission_note') ?></div><?php endif; ?>
                            </div>
                            <?php if (!empty($subs)): ?>
                                <?php if ($module_id === 'reports'): ?>
                                    <details class="perm-subs perm-reports">
                                        <summary><?= lang('Employees.reports_detail') ?> (<?= count($subs) ?>)</summary>
                                <?php else: ?>
                                    <div class="perm-subs">
                                <?php endif; ?>
                                <?php foreach ($subs as $permission): ?>
                                    <div class="form-check">
                                        <input class="form-check-input perm-sub-check" type="checkbox" name="grant_<?= $permission->permission_id ?>" value="<?= $permission->permission_id ?>" id="grant_<?= $permission->permission_id ?>" <?= $permission->grant == 1 ? 'checked' : '' ?> data-module="<?= $module_id ?>">
                                        <?= form_hidden("menu_group_$permission->permission_id", '--') ?>
                                        <label class="form-check-label perm-desc" for="grant_<?= $permission->permission_id ?>"><?= $permission->label ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <?= $module_id === 'reports' ? '</details>' : '</div>' ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </div>

    <button type="submit" hidden tabindex="-1" aria-hidden="true"></button>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        var $form = $('#employee_form');
        var $modal = $form.closest('.modal');
        var IS_NEW = <?= $is_new ? 'true' : 'false' ?>;
        var PROFILES = <?= json_encode(['cashier' => Module::PERMISSION_PROFILES['cashier'], 'admin' => '*']) ?>;
        var allow_close = false;
        var snapshot = '';
        var saving = false;

        $modal.closest('.bootstrap-dialog').addClass('items-dialog employee-dialog');

        var form_state = function() { return $form.serialize(); };
        var set_sending = function(sending) {
            saving = sending;
            $('#submit').prop('disabled', sending).css('opacity', '');
            $('#cancel').prop('disabled', sending);
        };

        // Enter on a button/checkbox/summary must not reach the dialog's Enter hotkey (it would save the form)
        $form.on('keyup', 'button, summary, input[type="checkbox"], input[type="radio"]', function(event) {
            if (event.which === 13) { event.stopPropagation(); }
        });

        // Create the Tab instances now: arrow-key navigation and the roving tabindex only exist once they do
        $('#employee_tabs [data-bs-toggle="tab"]').each(function() { bootstrap.Tab.getOrCreateInstance(this); });

        // ---- Tabs: find the tab that holds an element, show it
        var show_tab_for = function(el) {
            var pane_id = $(el).closest('.tab-pane').attr('id');
            if (pane_id) {
                bootstrap.Tab.getOrCreateInstance($('[data-bs-target="#' + pane_id + '"]')[0]).show();
            }
            var $details = $(el).closest('details');
            if ($details.length) { $details.prop('open', true); }
        };

        // ---- Eye buttons (password, repeat password, PIN)
        $('.toggle-secret').on('click', function() {
            var $btn = $(this);
            var $input = $($btn.data('target'));
            var show = $input.attr('type') === 'password';
            $input.attr('type', show ? 'text' : 'password');
            $btn.attr('aria-pressed', show ? 'true' : 'false');
            $btn.find('.icon-show').toggleClass('d-none', show);
            $btn.find('.icon-hide').toggleClass('d-none', !show);
        });

        // ---- PIN: digits only, generator (the backend checks it is not already in use)
        $('#pin').on('input', function() { this.value = this.value.replace(/\D/g, '').slice(0, 4); });
        $('#generate_pin').on('click', function() {
            var pin = String(Math.floor(Math.random() * 10000)).padStart(4, '0');
            $('#pin').val(pin).attr('type', 'text').trigger('input').focus();
            $('[data-target="#pin"]').attr('aria-pressed', 'true').find('.icon-show').addClass('d-none').end().find('.icon-hide').removeClass('d-none');
        });

        // ---- Permissions: profiles, groups, sub-permissions
        var $modules = $('.perm-module-check');
        var module_checks = function() { return $modules.filter(':not(:disabled)'); };

        var sync_subs = function($module) {
            var on = $module.is(':checked');
            $('.perm-sub-check[data-module="' + $module.val() + '"]').prop('disabled', !on).each(function() {
                if (!on) { $(this).prop('checked', false); }
            });
        };
        $modules.each(function() { sync_subs($(this)); });

        var current_profile = function() {
            var checked = $modules.filter(':checked').map(function() { return this.value; }).get().sort();
            var subs_checked = $('.perm-sub-check:checked').length;
            var all_modules = $modules.length === checked.length;
            var all_subs = $('.perm-sub-check').length === subs_checked;
            if (all_modules && all_subs) { return 'admin'; }
            if (subs_checked === 0 && JSON.stringify(checked) === JSON.stringify(PROFILES.cashier.slice().sort())) { return 'cashier'; }
            return 'custom';
        };
        var show_profile = function(name) {
            $('input[name="permission_profile"]').each(function() {
                var on = this.value === name;
                this.checked = on;
                $(this).parent().toggleClass('active', on);
            });
        };
        var refresh_group_buttons = function() {
            $('.perm-group').each(function() {
                var $boxes = $(this).find('input[type="checkbox"]:not(:disabled)');
                var all = $boxes.length > 0 && $boxes.filter(':checked').length === $boxes.length;
                var $btn = $(this).find('.perm-group-toggle');
                $btn.text(all ? $btn.data('unmark') : $btn.data('mark'));
            });
        };

        $('input[name="permission_profile"]').on('change', function() {
            var profile = this.value;
            if (profile === 'custom') { show_profile('custom'); return; }
            $modules.not(':disabled').each(function() {
                var on = profile === 'admin' || PROFILES.cashier.indexOf(this.value) !== -1;
                $(this).prop('checked', on);
            });
            $modules.each(function() { sync_subs($(this)); });
            if (profile === 'admin') { $('.perm-sub-check:not(:disabled)').prop('checked', true); }
            show_profile(profile);
            refresh_group_buttons();
        });

        // A manual tick turns the profile into "Personalizado"
        $('.perm-module-check, .perm-sub-check').on('change', function() {
            if ($(this).hasClass('perm-module-check')) { sync_subs($(this)); }
            show_profile('custom');
            refresh_group_buttons();
        });

        $('.perm-group-toggle').on('click', function() {
            var $group = $(this).closest('.perm-group');
            var $boxes = $group.find('input[type="checkbox"]:not(:disabled)');
            var all = $boxes.filter(':checked').length === $boxes.length;
            $group.find('.perm-module-check:not(:disabled)').prop('checked', !all).each(function() { sync_subs($(this)); });
            if (!all) { $group.find('.perm-sub-check:not(:disabled)').prop('checked', true); }
            show_profile('custom');
            refresh_group_buttons();
        });

        show_profile(IS_NEW ? 'custom' : current_profile());
        refresh_group_buttons();

        // ---- Validation (errors under each field)
        var clear_server_errors = function() {
            $('#branch_access-error, #permissions-error').text('');
            $form.find('.field-error[data-server]').each(function() {
                var id = this.id;
                $form.find('[aria-describedby~="' + id + '"]').each(function() {
                    var rest = ($(this).attr('aria-describedby') || '').split(' ').filter(function(part) { return part && part !== id; }).join(' ');
                    rest ? $(this).attr('aria-describedby', rest) : $(this).removeAttr('aria-describedby');
                    $(this).removeClass('is-invalid').removeAttr('aria-invalid');
                });
            }).remove();
        };

        var show_server_errors = function(errors) {
            var first = null;
            $.each(errors || {}, function(field, message) {
                if (field === 'branch_access' || field === 'permissions') {
                    var $box = $('#' + field + '-error').text(message);
                    first = first || $box.closest('.tab-pane').find('input:not([type=hidden]):first')[0];
                    return;
                }
                var $field = $form.find('[name="' + field + '"]').not('[type=hidden]').first();
                if ($field.length) {
                    var $anchor = $field.closest('.input-group').length ? $field.closest('.input-group') : $field;
                    $('<div class="field-error" role="alert" data-server="1"></div>').attr('id', field + '-error').text(message).insertAfter($anchor);
                    $field.addClass('is-invalid').attr({'aria-invalid': 'true', 'aria-describedby': (($field.attr('aria-describedby') || '') + ' ' + field + '-error').trim()});
                    first = first || $field[0];
                }
            });
            if (first) {
                show_tab_for(first);
                setTimeout(function() { first.focus && first.focus(); }, 80);
            }
        };

        var do_submit = function(form) {
            set_sending(true);
            clear_server_errors();
            $(form).ajaxSubmit({
                dataType: 'json',
                success: function(response) {
                    set_sending(false);
                    if (response.success) {
                        allow_close = true;
                        bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
                        table_support.handle_submit("<?= esc($controller_name) ?>", response);
                    } else {
                        show_server_errors(response.errors || {});
                        if (!response.errors) { $.notify(response.message, {type: 'danger'}); }
                    }
                },
                error: function() {
                    set_sending(false);
                    $.notify("<?= esc(lang('Employees.save_error_connection'), 'js') ?>", {type: 'danger'});
                }
            });
        };

        $form.validate({
            ignore: [],
            onkeyup: false,
            focusInvalid: false,
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
            highlight: function(element) { $(element).addClass('is-invalid').attr('aria-invalid', 'true'); },
            unhighlight: function(element) { $(element).removeClass('is-invalid').removeAttr('aria-invalid'); },
            invalidHandler: function(event, validator) {
                var first = validator.errorList.length ? validator.errorList[0].element : null;
                if (first) {
                    show_tab_for(first);
                    setTimeout(function() { first.focus(); }, 80);
                }
            },
            submitHandler: function(form) {
                if (saving) { return; }
                var none_ticked = $form.find('input[name="branch_access[]"]:checked').length === 0
                    && $form.find('input[name="branch_access[]"]').length > 0;
                if (none_ticked) {
                    BootstrapDialog.confirm({
                        title: "<?= esc(lang('Employees.branches_title'), 'js') ?>",
                        message: "<?= esc(lang('Employees.branches_confirm'), 'js') ?>",
                        type: BootstrapDialog.TYPE_WARNING,
                        btnOKLabel: "<?= esc(lang('Employees.save'), 'js') ?>",
                        btnCancelLabel: "<?= esc(lang('Employees.cancel'), 'js') ?>",
                        callback: function(ok) { ok && do_submit(form); }
                    });
                    return;
                }
                do_submit(form);
            },
            rules: {
                first_name: 'required',
                last_name: 'required',
                username: {
                    required: true,
                    minlength: 5,
                    remote: '<?= esc("$controller_name/checkUsername/$employee_id") ?>'
                },
                password: {
                    <?php if ($is_new) { ?>required: true,<?php } ?>
                    minlength: 8
                },
                repeat_password: {
                    equalTo: '#password'
                },
                email: 'email',
                pin: {
                    minlength: 4
                }
            },
            messages: {
                first_name: "<?= lang('Common.first_name_required') ?>",
                last_name: "<?= lang('Common.last_name_required') ?>",
                username: {
                    required: "<?= lang('Employees.username_required') ?>",
                    minlength: "<?= lang('Employees.username_minlength') ?>",
                    remote: "<?= lang('Employees.username_duplicate') ?>"
                },
                password: {
                    <?php if ($is_new) { ?>required: "<?= lang('Employees.password_required') ?>",<?php } ?>
                    minlength: "<?= lang('Employees.password_minlength') ?>"
                },
                repeat_password: {
                    equalTo: "<?= lang('Employees.password_must_match') ?>"
                },
                email: "<?= lang('Common.email_invalid_format') ?>",
                pin: { minlength: "<?= lang('Employees.pin_invalid') ?>" }
            }
        });

        // ---- Unsaved changes: closing (X, Escape, click outside, Cancelar) asks first
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
            $bar.find('#discard-text').text("<?= esc(lang('Employees.discard_changes'), 'js') ?>");
            $bar.find('.btn-discard').text("<?= esc(lang('Employees.discard'), 'js') ?>").on('click', function() {
                allow_close = true;
                bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            });
            $bar.find('.btn-keep').text("<?= esc(lang('Employees.keep_editing'), 'js') ?>").on('click', function() {
                $bar.remove();
                $('#first_name').trigger('focus');
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

        setTimeout(function() { $('#first_name').trigger('focus'); }, 150);
    });
</script>
