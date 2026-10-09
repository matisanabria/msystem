<?php
/**
 * Date range shortcuts (Today, Yesterday, This week, This month) for a manage view that already ran
 * partial/daterangepicker (it defines start_date / end_date). Buttons: .range-shortcut[data-range]; field: #daterangepicker.
 * Emits JS; include inside the document-ready handler, after the daterangepicker partial.
 *
 * @var array $config
 */
?>
var has_time = <?= empty($config['date_or_time_format']) ? 'false' : 'true' ?>;

// Show the whole range in the field
var fit_date_field = function() {
    var $field = $('#daterangepicker');
    $field.css('width', (String($field.val()).length + 3) + 'ch');
};

var shortcut_range = function(name) {
    var today = moment();
    switch (name) {
        case 'yesterday':
            return [moment().subtract(1, 'day').startOf('day'), moment().subtract(1, 'day').endOf('day')];
        case 'week':
            return [moment().startOf('isoWeek'), today.endOf('day')];
        case 'month':
            return [moment().startOf('month'), today.endOf('day')];
        default:
            return [moment().startOf('day'), today.endOf('day')];
    }
};

var range_param = function(m) {
    return m.format(has_time ? 'YYYY-MM-DD HH:mm:ss' : 'YYYY-MM-DD');
};

var sync_shortcuts = function() {
    $('.range-shortcut').each(function() {
        var range = shortcut_range($(this).data('range'));
        $(this).attr('aria-pressed', range_param(range[0]) === start_date && range_param(range[1]) === end_date ? 'true' : 'false');
    });
};

$('.range-shortcut').on('click', function() {
    var picker = $('#daterangepicker').data('daterangepicker');
    var range = shortcut_range($(this).data('range'));
    picker.setStartDate(range[0]);
    picker.setEndDate(range[1]);
    start_date = range_param(range[0]);
    end_date = range_param(range[1]);
    fit_date_field();
    sync_shortcuts();
    table_support.refresh();
});

$('#daterangepicker').on('apply.daterangepicker', function() {
    fit_date_field();
    sync_shortcuts();
    table_support.refresh();
});

fit_date_field();
sync_shortcuts();
