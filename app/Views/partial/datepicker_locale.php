<?php
/**
 * Tempus Dominus (Bootstrap 5) date/time picker setup.
 * Optional view data: $format (date only format, no clock is shown when given).
 */

use Config\OSPOS;

$config = config(OSPOS::class)->settings;

$date_only    = isset($format);
$picker_format = $format ?? dateformat_tempus($config['dateformat']) . ' ' . dateformat_tempus($config['timeformat']);
$meridian     = str_contains($config['timeformat'], 'a') || str_contains($config['timeformat'], 'A');
?>

var pickerconfig = function(config) {
    return $.extend(true, {
        allowInputToggle: true,
        useCurrent: false,
        stepping: 1,
        localization: {
            locale: "<?= esc(str_replace('_', '-', current_language_code()), 'js') ?>",
            format: "<?= esc($picker_format, 'js') ?>",
            hourCycle: "<?= $meridian ? 'h12' : 'h23' ?>",
            startOfTheWeek: <?= (int) lang('Datepicker.weekstart') ?>,
            dayViewHeaderFormat: {month: 'long', year: 'numeric'},
            today: "<?= esc(lang('Datepicker.today'), 'js') ?>"
        },
        display: {
            theme: 'light',
            components: {
                clock: <?= $date_only ? 'false' : 'true' ?>,
                seconds: false
            },
            buttons: {
                today: true,
                clear: false,
                close: true
            },
            icons: {
                time: 'bi bi-clock',
                date: 'bi bi-calendar3',
                up: 'bi bi-chevron-up',
                down: 'bi bi-chevron-down',
                previous: 'bi bi-chevron-left',
                next: 'bi bi-chevron-right',
                today: 'bi bi-calendar-check',
                clear: 'bi bi-trash',
                close: 'bi bi-x-lg'
            }
        }
    }, config);
};

$(".datetime").each(function() {
    new tempusDominus.TempusDominus(this, pickerconfig());
});
