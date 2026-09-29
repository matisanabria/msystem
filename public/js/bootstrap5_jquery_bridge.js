/**
 * Bootstrap 5 only registers its jQuery plugins ($.fn.modal, $.fn.dropdown, ...) on DOMContentLoaded.
 * Plugins loaded afterwards in the bundle (bootstrap5-dialog, bootstrap-select, bootstrap-table) read
 * $.fn.modal.Constructor / $.fn.dropdown.Constructor while loading, so register them right away.
 */
(function($, bootstrap) {
    if (!$ || !bootstrap) {
        return;
    }

    $.each(['Alert', 'Button', 'Carousel', 'Collapse', 'Dropdown', 'Modal', 'Offcanvas', 'Popover', 'ScrollSpy', 'Tab', 'Toast', 'Tooltip'], function(index, name) {
        var plugin = bootstrap[name];
        var key = name.charAt(0).toLowerCase() + name.slice(1);

        if (plugin && typeof plugin.jQueryInterface === 'function' && !$.fn[key]) {
            $.fn[key] = plugin.jQueryInterface;
            $.fn[key].Constructor = plugin;
            $.fn[key].noConflict = function() {
                return plugin.jQueryInterface;
            };
        }
    });
})(window.jQuery, window.bootstrap);
