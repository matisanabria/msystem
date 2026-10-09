/**
 * $.notify shim on top of Bootstrap 5 toasts (replaces bootstrap-notify).
 * Supports $.notify(message, {type}) and $.notify({message}, {type}), plus $.notifyDefaults({placement}).
 */
(function($) {

    var placement = {align: 'right', from: 'top'};
    var delay = 5000;

    var container = function() {
        var $container = $('#toast-container');
        if (!$container.length) {
            $container = $('<div id="toast-container" class="toast-container position-fixed p-3"></div>').appendTo('body');
        }

        var horizontal = {left: 'start-0', center: 'start-50 translate-middle-x', right: 'end-0'};
        var vertical = {top: 'top-0', bottom: 'bottom-0'};

        return $container
            .removeClass('start-0 start-50 translate-middle-x end-0 top-0 bottom-0')
            .addClass((horizontal[placement.align] || horizontal.right) + ' ' + (vertical[placement.from] || vertical.top));
    };

    $.notifyDefaults = function(defaults) {
        $.extend(true, placement, (defaults && defaults.placement) || {});
        if (defaults && defaults.delay !== undefined) {
            delay = defaults.delay;
        }
    };

    $.notify = function(content, options) {
        var message = (content !== null && typeof content === 'object') ? content.message : content;
        var type = (options && options.type) || 'info';
        var close_class = type === 'warning' || type === 'light' ? '' : ' btn-close-white';

        var $toast = $(
            '<div class="toast align-items-center text-bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="d-flex">' +
                    '<div class="toast-body"></div>' +
                    '<button type="button" class="btn-close' + close_class + ' me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                '</div>' +
            '</div>'
        );
        // message is expected to be sanitized by the caller (see header_js.php), HTML is allowed as before
        $toast.find('.toast-body').html(message);
        $toast.appendTo(container()).on('hidden.bs.toast', function() {
            $toast.remove();
        });

        bootstrap.Toast.getOrCreateInstance($toast[0], {delay: (options && options.delay) || delay}).show();
        return $toast;
    };

})(jQuery);

(function(dialog_support, $) {

    var btn_id, dialog_ref;

    var hide = function() {
        dialog_ref && dialog_ref.close();
    };

    var clicked_id = function() {
        return btn_id;
    };

    var submit = function(button_id) {
        return function(dlog_ref) {
            const form = $('form', dlog_ref.$modalBody).first();
            const validator = form.data('validator');
            const submitted = validator && validator.formSubmitted;

            btn_id = button_id;
            dialog_ref = dlog_ref;

            if (button_id == 'cancel') {
                dlog_ref.close();
                return false;
            }

            if (button_id == 'submit' && (!submitted && btn_id != "btnNew")) {
                form.submit();
                validator.valid() && $('#submit').prop('disabled', true).css('opacity', 0.5);
            }
            return false;
        }
    };

    var button_class = {
        'submit' : 'btn-primary',
        'delete' : 'btn-danger',
        'cancel' : 'btn-outline-secondary'
    };

    var init = function(selector) {

        var buttons = function(event) {
            var $trigger = $(this);
            var buttons = [];
            var dialog_class = 'modal-dlg';
            $.each($trigger.attr('class').split(/\s+/), function(classIndex, className) {
                var width_class = className.split("modal-dlg-");
                if (width_class && width_class.length > 1) {
                    dialog_class = className;
                }
            });

            var has_new_btn = "btnNew" in $trigger.data();
            $.each($trigger.data(), function(name, value) {
                var btn_class = name.split("btn");
                if (btn_class && btn_class.length > 1) {
                    var btn_name = btn_class[1].toLowerCase();
                    var is_submit = btn_name == 'submit';
                    var is_new = btn_name === 'new';
                    var has_cancel_btn = "btnCancel" in $trigger.data();
                    // With an explicit Cancel button the Enter hotkey would also fire from it, and text inputs already submit natively
                    var is_enter = has_cancel_btn ? false : (has_new_btn ? is_new: is_submit);
                    buttons.push({
                        id: btn_name,
                        label: value,
                        title: $trigger.data('hint' + btn_class[1]),
                        cssClass: button_class[btn_name],
                        hotkey: is_enter ? 13 : undefined, // Enter
                        action: submit(btn_name)
                    });
                }
            });

            !buttons.length && buttons.push({
                id: 'close',
                label: lang.line('common_close'),
                cssClass: 'btn-primary',
                action: function(dialog_ref) {
                    dialog_ref.close();
                }
            });
            return { buttons: buttons.sort(function(a, b) {
                return ($(b).text()) < ($(a).text()) ? -1 : 1;
            }), cssClass: dialog_class};
        };

        $(selector).each(function(index, $element) {

            return $(selector).off('click').on('click', function(event) {
                var $link = $(event.target);
                $link = !$link.is("a, button") ? $link.parents("a, button") : $link ;
                BootstrapDialog.show($.extend({
                    title: $link.attr('title'),
                    message: (function() {
                        var node = $('<div></div>');
                        $.get($link.attr('href') || $link.data('href'), function(data) {
                            node.html(data);
                        });
                        return node;
                    })
                }, buttons.call(this, event)));

                return false;
            });
        });
    };

    $.extend(dialog_support, {
        init: init,
        submit: submit,
        hide: hide,
        clicked_id: clicked_id
    });

})(window.dialog_support = window.dialog_support || {}, jQuery);

(function(table_support, $) {

    var enable_actions = function(callback) {
        return function() {
            var selection_empty = selected_rows().length == 0;
            $("#toolbar button:not(.dropdown-toggle):not([data-always-enabled])").attr('disabled', selection_empty);
            typeof callback == 'function' && callback();
        }
    };

    var table = function() {
        return $("#table").data('bootstrap.table');
    }

    var selected_ids = function () {
        return $.map(table().getSelections(), function (element) {
            return element[options.uniqueId || 'id'] !== '-' ? element[options.uniqueId || 'id'] : null;
        });
    };

    var selected_rows = function () {
        return $("#table td input:checkbox:checked").parents("tr");
    };

    var row_selector = function(id) {
        return "tr[data-uniqueid='" + id + "']";
    };

    var rows_selector = function(ids) {
        var selectors = [];
        ids = ids instanceof Array ? ids : ("" + ids).split(":");
        $.each(ids, function(index, element) {
            selectors.push(row_selector(element));
        });
        return selectors;;
    };

    var highlight_row = function (id, color) {
        $(rows_selector(id)).each(function(index, element) {
            var original = $(element).css('backgroundColor');
            $(element).find("td").animate({backgroundColor: color || '#e1ffdd'}, "slow", "linear")
                .animate({backgroundColor: color || '#e1ffdd'}, (options && options.highlightHold) || 5000)
                .animate({backgroundColor: original}, "slow", "linear", function() {
                    // Hand the cell background back to the stylesheet (sticky cells need their own)
                    $(this).css('background-color', '');
                });
        });
    };

    var do_action = function(action) {
        return function (url, ids) {
            var confirm_message = typeof options.confirmMessage == 'function'
                    ? options.confirmMessage(action, ids || selected_ids())
                    : $.fn.bootstrapTable.defaults.formatConfirmAction(action);
                if (confirm(confirm_message)) {
                $.post((url || options.resource) + '/' + action, {'ids[]': ids || selected_ids()}, function (response) {
                    // Delete was successful, remove checkbox rows
                    if (response.success) {
                        var selector = ids ? row_selector(ids) : selected_rows();
                        table().collapseAllRows();
                        $(selector).each(function (index, element) {
                            $(this).find("td").animate({backgroundColor: "green"}, 1200, "linear")
                                .end().animate({opacity: 0}, 1200, "linear", function () {
                                table().remove({
                                    field: options.uniqueId,
                                    values: selected_ids()
                                });
                                if (index == $(selector).length - 1) {
                                    refresh();
                                    enable_actions();
                                }
                            });
                        });
                        $.notify(response.message, {type: 'success'});
                    } else {
                        $.notify(response.message, {type: 'danger'});
                    }
                }, "json");
            } else {
                return false;
            }
        };
    };

    var load_success = function(callback) {
        return function(response) {
            typeof options.load_callback == 'function' && options.load_callback();
            options.load_callback = undefined;
            dialog_support.init("a.modal-dlg");
            typeof callback == 'function' && callback.call(this, response);
        }
    };

    var options;

    var toggle_column_visibility = function() {
        if (localStorage[options.employee_id]) {
            var user_settings = JSON.parse(localStorage[options.employee_id]);
            user_settings[options.resource] && $.each(user_settings[options.resource], function(index, element) {
                element ? table().showColumn(index) : table().hideColumn(index);
            });
        }
    };

    var init = function (_options) {
        options = _options;
        enable_actions = enable_actions(options.enableActions);
        load_success = load_success(options.onLoadSuccess);
        const export_suffix = new Date().toISOString().slice(0, 16).replace(/(-|\s*|T|:)*/g,"");
        $('#table')
            .addClass("table-striped")
            .addClass("table-bordered")
            .bootstrapTable($.extend(options, {
            columns: options.headers,
            stickyHeader: true,
            url: options.resource + '/search',
            sidePagination: 'server',
            selectItemName: 'btSelectItem',
            pageSize: options.pageSize,
            pagination: true,
            search: options.resource || false,
            showColumns: true,
            clickToSelect: true,
            showExport: true,
            exportDataType: 'basic',
            exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
            exportOptions: {
                fileName: options.resource.replace(/.*\/(.*?)$/g, '$1') + "_" + export_suffix
            },
            onPageChange: function(response) {
                load_success(response);
                enable_actions();
            },
            toolbar: '#toolbar',
            uniqueId: options.uniqueId || 'id',
            trimOnSearch: false,
            onCheck: enable_actions,
            onUncheck: enable_actions,
            onCheckAll: enable_actions,
            onUncheckAll: enable_actions,
            onLoadSuccess: function(response) {
                load_success(response);
                enable_actions();
            },
            onColumnSwitch : function(field, checked) {
                var user_settings = localStorage[options.employee_id];
                user_settings = (user_settings && JSON.parse(user_settings)) || {};
                user_settings[options.resource] = user_settings[options.resource] || {};
                user_settings[options.resource][field] = checked;
                localStorage[options.employee_id] = JSON.stringify(user_settings);
                dialog_support.init("a.modal-dlg");
            },
            queryParamsType: 'limit',
            iconSize: 'sm',
            silentSort: true,
            paginationVAlign: 'bottom',
            escape: true
        }));
        enable_actions();
        init_delete();
        init_restore();
        toggle_column_visibility();
        dialog_support.init("button.modal-dlg");
    };

    var init_delete = function (confirmMessage) {
        $("#delete").click(function(event) {
            do_action("delete")();
        });
    };

    var init_restore = function (confirmMessage) {
        $("#restore").click(function(event) {
            do_action("restore")();
        });
    };

    var refresh = function() {
        table().refresh();
    }

    var submit_handler = function(url) {
        return function (resource, response) {
            var id = response.id !== undefined ? response.id.toString() : "";
            if (!response.success) {
                $.notify(response.message, { type: 'danger' });
            } else {
                var message = response.message;
                var selector = rows_selector(response.id);
                var rows = $(selector.join(",")).length;
                if (rows > 0 && rows < 15) {
                    var ids = id.split(":");
                    $.get([url || resource + '/row', id].join("/"), {}, function (response) {
                        $.each(selector, function (index, element) {
                            var id = $(element).data('uniqueid');
                            table().updateByUniqueId({id: id, row: response[id] || response});
                        });
                        dialog_support.init("a.modal-dlg");
                        highlight_row(ids);
                    }, 'json');
                } else {
                    // Call hightlight function once after refresh
                    options.load_callback = function () {
                        enable_actions();
                        highlight_row(id);
                    };
                    refresh();
                }
                $.notify(message, {type: 'success' });
            }
            return false;
        };
    };

    var handle_submit = submit_handler();

    $.extend(table_support, {
        submit_handler: function(url) {
            this.handle_submit = submit_handler(url);
        },
        handle_submit: handle_submit,
        init: init,
        do_delete: do_action("delete"),
        do_restore: do_action("restore"),
        refresh : refresh,
        selected_ids : selected_ids,
    });

})(window.table_support = window.table_support || {}, jQuery);

(function(form_support, $) {

    form_support.error = {
        errorClass: "has-error",
        errorLabelContainer: "#error_message_box",
        wrapper: "li",
        highlight: function (e) {
            $(e).addClass('is-invalid').closest('.form-group').addClass('has-error');
        },
        unhighlight: function (e) {
            $(e).removeClass('is-invalid').closest('.form-group').removeClass('has-error');
        }
    };

    form_support.handler = $.extend({

        submitHandler: function(form) {
            $(form).ajaxSubmit({
                success: function(response)
                {
                    $.notify(response.message, { type: response.success ? 'success' : 'danger' });
                },
                dataType: 'json'
            });
        },

        rules:
        {

        },

        messages:
        {

        }
    }, form_support.error);

})(window.form_support = window.form_support || {}, jQuery);

/**
 * Native file input with image preview and remove button (replaces jasny-bootstrap fileinput).
 * Markup: [data-image-input] > .image-input-preview img, label > .image-input-label + input[type=file], .image-input-remove
 */
(function($) {

    var set_state = function($root, exists) {
        var $label = $root.find('.image-input-label');
        $root.find('.image-input-preview').toggleClass('d-none', !exists);
        $root.find('.image-input-remove').toggleClass('d-none', !exists);
        $label.text($label.data(exists ? 'change' : 'select'));
    };

    $(document).on('change', '[data-image-input] input[type="file"]', function() {
        var $root = $(this).closest('[data-image-input]');
        var file = this.files && this.files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            $root.find('.image-input-preview img').attr('src', e.target.result);
            set_state($root, true);
        };
        reader.readAsDataURL(file);
    });

    $(document).on('click', '[data-image-input] .image-input-remove', function() {
        var $root = $(this).closest('[data-image-input]');
        $root.find('input[type="file"]').val('');
        $root.find('.image-input-preview img').attr('src', '');
        set_state($root, false);
    });

})(jQuery);

/**
 * Tag chips for <select multiple data-role="tagsinput"> (replaces bootstrap-tagsinput).
 * The select stays in the form (hidden) with every tag as a selected option, so the POST is unchanged.
 */
(function($) {

    var init = function(select) {
        var $select = $(select);
        if ($select.data('tags-input')) {
            return;
        }
        $select.data('tags-input', true).addClass('d-none').find('option').prop('selected', true);

        var $box = $('<div class="tags-input form-control form-control-sm d-flex flex-wrap gap-1 align-items-center"></div>');
        var $input = $('<input type="text" autocomplete="off">');
        $box.append($input).insertAfter($select);

        var exists = function(value) {
            return $select.find('option').filter(function() { return this.value === value; }).length > 0;
        };

        var add_chip = function(value) {
            var $chip = $('<span class="badge text-bg-secondary d-inline-flex align-items-center"></span>').text(value);
            $('<button type="button" class="btn-close btn-close-white ms-1" aria-label="Remove"></button>')
                .appendTo($chip)
                .on('click', function() {
                    $select.find('option').filter(function() { return this.value === value; }).remove();
                    $chip.remove();
                    $input.trigger('focus');
                });
            $chip.insertBefore($input);
        };

        var add = function() {
            var value = $.trim($input.val()).replace(/,+$/, '');
            $input.val('');
            if (value === '' || exists(value)) {
                return;
            }
            $('<option></option>').val(value).text(value).prop('selected', true).appendTo($select);
            add_chip(value);
        };

        $select.find('option').each(function() { add_chip(this.value); });

        $input.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                add();
            } else if (e.key === 'Backspace' && $input.val() === '') {
                $box.find('.btn-close').last().trigger('click');
            }
        }).on('blur', add);

        $box.on('click', function() { $input.trigger('focus'); });
    };

    $(function() {
        $('select[data-role="tagsinput"]').each(function() { init(this); });
    });

})(jQuery);

function number_sorter(a, b) {
    a = +a.replace(/[^\-0-9]+/g, '');
    b = +b.replace(/[^\-0-9]+/g, '');
    return a - b;
}
