<?php
/**
 * @var object $user_info
 * @var array $allowed_modules
 * @var CodeIgniter\HTTP\IncomingRequest $request
 * @var array $config
 */

use Config\Services;

$request = Services::request();
?>

<!doctype html>
<html lang="<?= $request->getLocale() ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?= base_url() ?>">
    <title><?= esc($config['company']) . ' | Sistema de Gestión' ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    <?php
    // paper and readable are not available in Bootswatch 5
    $theme = (empty($config['theme']) || in_array($config['theme'], ['paper', 'readable'], true)) ? 'flatly' : $config['theme'];
    ?>
    <link rel="stylesheet" href="<?= 'resources/bootswatch/' . esc($theme) . '/bootstrap.min.css' ?>">
    <link rel="stylesheet" href="resources/bootstrap-icons/bootstrap-icons.min.css">

    <?php if (ENVIRONMENT == 'development' || get_cookie('debug') == 'true' || $request->getGet('debug') == 'true') : ?>
        <!-- inject:debug:css -->
        <link rel="stylesheet" href="resources/css/jquery-ui-fe010342cb.css">
        <link rel="stylesheet" href="resources/css/bootstrap-dialog-535f55a401.css">
        <link rel="stylesheet" href="resources/css/tempus-dominus-ccd380cd8e.css">
        <link rel="stylesheet" href="resources/css/bootstrap-select-e0658d7d20.css">
        <link rel="stylesheet" href="resources/css/bootstrap-table-ed9d1a3360.css">
        <link rel="stylesheet" href="resources/css/bootstrap-table-sticky-header-07d65e7533.css">
        <link rel="stylesheet" href="resources/css/daterangepicker-85523b7dfe.css">
        <link rel="stylesheet" href="resources/css/chartist-c19aedb81a.css">
        <link rel="stylesheet" href="resources/css/chartist-plugin-tooltip-2e0ec92e60.css">
        <link rel="stylesheet" href="resources/css/bootstrap-292fc0ad3b.autocomplete.css">
        <link rel="stylesheet" href="resources/css/invoice-1eae5e39b9.css">
        <link rel="stylesheet" href="resources/css/ospos_print-2ba645b044.css">
        <link rel="stylesheet" href="resources/css/ospos-66d8156c40.css">
        <link rel="stylesheet" href="resources/css/popupbox-7b616030b0.css">
        <link rel="stylesheet" href="resources/css/receipt-a171207d8e.css">
        <link rel="stylesheet" href="resources/css/register-d8321cb86f.css">
        <link rel="stylesheet" href="resources/css/reports-407b727797.css">
        <!-- endinject -->
        <!-- inject:debug:js -->
        <script src="resources/js/jquery-12e87d2f3a.js"></script>
        <script src="resources/js/jquery-4fa896f615.form.js"></script>
        <script src="resources/js/jquery-a0350e8820.validate.js"></script>
        <script src="resources/js/jquery-ui-cbc65ff85e.js"></script>
        <script src="resources/js/popper-063a0fe0ef.js"></script>
        <script src="resources/js/bootstrap-52de67605c.js"></script>
        <script src="resources/js/bootstrap5_jquery_bridge-fba1c7bdd5.js"></script>
        <script src="resources/js/bootstrap-dialog-ea1d8d45a8.js"></script>
        <script src="resources/js/tempus-dominus-610b31b9fd.js"></script>
        <script src="resources/js/bootstrap-select-146babba2b.js"></script>
        <script src="resources/js/bootstrap-table-bdb06552ea.js"></script>
        <script src="resources/js/bootstrap-table-export-6389dc2aa5.js"></script>
        <script src="resources/js/bootstrap-table-mobile-fc655b68ab.js"></script>
        <script src="resources/js/bootstrap-table-sticky-header-cb4d83d172.js"></script>
        <script src="resources/js/moment-d65dc6d2e6.min.js"></script>
        <script src="resources/js/daterangepicker-048c56a690.js"></script>
        <script src="resources/js/es6-promise-855125e6f5.js"></script>
        <script src="resources/js/FileSaver-e73b1946e8.js"></script>
        <script src="resources/js/html2canvas-e1d3a8d7cd.js"></script>
        <script src="resources/js/jspdf-81c4900447.umd.js"></script>
        <script src="resources/js/purify-f71b63cae1.js"></script>
        <script src="resources/js/jspdf-92d87e47e8.plugin.autotable.js"></script>
        <script src="resources/js/tableExport-3d506dfa61.min.js"></script>
        <script src="resources/js/chartist-8a7ecb4445.js"></script>
        <script src="resources/js/chartist-plugin-pointlabels-0a1ab6aa4e.js"></script>
        <script src="resources/js/chartist-plugin-tooltip-116cb48831.js"></script>
        <script src="resources/js/chartist-plugin-axistitle-80a1198058.js"></script>
        <script src="resources/js/chartist-plugin-barlabels-4165273742.js"></script>
        <script src="resources/js/clipboard-908af414ab.js"></script>
        <script src="resources/js/imgpreview-62e42c15a0.full.jquery.js"></script>
        <script src="resources/js/manage_tables-8279a34ebb.js"></script>
        <script src="resources/js/nominatim-599d9d6f9c.autocomplete.js"></script>
        <!-- endinject -->
    <?php else : ?>
        <!--inject:prod:css -->
        <link rel="stylesheet" href="resources/opensourcepos-3eb9f2570d.min.css">
        <!-- endinject -->

        <!-- Tweaks to the UI for a particular theme should drop here  -->
        <?php if ($config['theme'] != 'flatly' && file_exists($_SERVER['DOCUMENT_ROOT'] . '/public/css/' . esc($config['theme']) . '.css')) { ?>
            <link rel="stylesheet" href="<?= 'css/' . esc($config['theme']) . '.css' ?>">
        <?php } ?>
        <!-- inject:prod:js -->
        <script src="resources/jquery-2c872dbe60.min.js"></script>
        <script src="resources/opensourcepos-c7c1edcb65.min.js"></script>
        <!-- endinject -->
    <?php endif; ?>

    <?= view('partial/header_js') ?>
    <?= view('partial/lang_lines') ?>

    <style>
        html {
            overflow: auto;
        }

        /* Scrollbars siempre visibles en Safari/macOS (por defecto son invisibles) */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
            background: #f0f0f0;
        }
        ::-webkit-scrollbar-thumb {
            background: #aaa;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #888;
        }

        /* Scroll horizontal en el contenedor de la tabla */
        .fixed-table-body,
        #table_holder {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Contenedor más ancho en pantallas de laptop (MacBook 1280-1800px) */
        @media (min-width: 1200px) {
            .container {
                width: 96%;
                max-width: 1700px;
            }
        }

        @media (max-width: 767.98px) {
            .topbar .navbar-left  { display: none; }
            .topbar .navbar-center { display: none; }
            .topbar .navbar-right {
                float: none;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 6px 8px;
                white-space: nowrap;
            }
            .topbar .navbar-right a {
                max-width: 42vw;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                display: inline-block;
                vertical-align: bottom;
            }
            .topbar .navbar-right span {
                flex: none;
                opacity: .5;
            }
            .topbar .container { padding: 0 8px; }

            /* Module menu: proper touch list instead of the desktop icon grid */
            .navbar .navbar-collapse.show,
            .navbar .navbar-collapse.collapsing {
                border-top: 1px solid rgba(255, 255, 255, .08);
            }

            .navbar .navbar-nav {
                margin: 0;
            }

            .navbar .navbar-nav > .nav-item {
                border-bottom: 1px solid rgba(255, 255, 255, .08);
            }

            .navbar .navbar-nav > .nav-item:last-child {
                border-bottom: none;
            }

            .navbar .navbar-nav > .nav-item > a.menu-icon {
                display: flex;
                align-items: center;
                gap: 14px;
                text-align: left;
                padding: 12px 16px;
                font-size: 14px;
            }

            .navbar .navbar-nav > .nav-item > a.menu-icon.active {
                border-bottom-color: transparent;
                box-shadow: inset 3px 0 0 #18bc9c;
                background-color: rgba(255, 255, 255, .06);
            }

        }
    </style>
</head>

<?php
$module_icons = [
    'home'                => 'house-door',
    'office'              => 'building',
    'customers'           => 'people',
    'employees'           => 'person-badge',
    'service_tickets'     => 'phone',
    'assistances'         => 'shield-check',
    'items'               => 'box-seam',
    'item_kits'           => 'boxes',
    'suppliers'           => 'truck',
    'reports'             => 'bar-chart-line',
    'receivings'          => 'box-arrow-in-down',
    'sales'               => 'cart3',
    'giftcards'           => 'gift',
    'logs'                => 'journal-text',
    'messages'            => 'chat-dots',
    'attributes'          => 'sliders',
    'expenses'            => 'receipt',
    'expenses_categories' => 'tags',
    'cashups'             => 'cash-stack',
    'admin_panel'         => 'gear',
    'discount_approvals'  => 'patch-check',
    'inventory_output'    => 'box-arrow-up',
    'config'              => 'gear-wide-connected',
    'taxes'               => 'receipt-cutoff',
];
?>
<body>
    <div class="wrapper">
        <div class="topbar">
            <div class="container">
                <div class="navbar-left">
                    <div id="liveclock"><?= date($config['dateformat'] . ' ' . $config['timeformat']) ?></div>
                </div>

                <div class="navbar-right" style="margin: 0;">
                    <?= anchor("home/changePassword/$user_info->person_id", "$user_info->first_name $user_info->last_name", ['class' => 'modal-dlg', 'data-btn-submit' => lang('Common.submit'), 'title' => lang('Employees.change_password')]) ?>
                    <span>&nbsp;|&nbsp;</span>
                    <?= anchor('home/logout', lang('Login.logout')) ?>
                </div>

                <div class="navbar-center" style="text-align: center;">
                    <strong><?= esc($config['company']) ?></strong>
                </div>
            </div>
        </div>

        <nav class="navbar navbar-expand-md navbar-dark navbar-ospos">
            <div class="container">
                <a class="navbar-brand d-md-none d-lg-inline" href="<?= site_url() ?>">Sistema de Gestión</a>

                <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbar-modules" aria-controls="navbar-modules" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbar-modules">
                    <ul class="navbar-nav ms-auto">
                        <?php foreach ($allowed_modules as $module): ?>
                            <li class="nav-item">
                                <a href="<?= base_url($module->module_id) ?>" title="<?= lang("Module.$module->module_id") ?>" class="nav-link menu-icon <?= $module->module_id == $request->getUri()->getSegment(1) ? 'active' : '' ?>">
                                    <span class="bi bi-<?= $module_icons[$module->module_id] ?? 'app' ?>" aria-hidden="true"></span>
                                    <?= lang('Module.' . $module->module_id) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container pt-3">
            <div class="row">

<?php
$_emp_model  = model(\App\Models\Employee::class);
$_person_id  = session()->get('person_id');
if ($_emp_model->has_grant('discount_approvals', $_person_id)):
?>
<div id="discount_toast_container" style="position:fixed; bottom:20px; right:20px; z-index:9999; width:340px;"></div>
<script>
(function() {
    var _knownIds        = [];
    var _initialized     = false;
    var _warnedThreshold = false;

    var $notif_banner = $('<div id="da_notif_banner">')
        .css({position:'fixed', top:'0', left:'0', right:'0', zIndex:10000,
              background:'#e67e22', color:'#fff', padding:'8px 16px',
              fontSize:'13px', textAlign:'center', display:'none'})
        .html('<span class="bi bi-bell"></span>&nbsp;<span id="da_notif_msg"></span>' +
              '&nbsp;<button type="button" style="margin-left:12px; padding:2px 10px; font-size:12px;" class="btn btn-sm btn-light" id="da_notif_enable_btn" style="display:none;">Habilitar</button>' +
              '&nbsp;<button type="button" style="margin-left:4px; padding:2px 8px; font-size:12px;" class="btn btn-sm btn-light" id="da_notif_dismiss">&times;</button>');

    $(document).ready(function() { $('body').prepend($notif_banner); check_notif_status(); });
    $('#da_notif_dismiss', $notif_banner).on('click', function() {
        $notif_banner.slideUp();
        try { localStorage.setItem('da_notif_denied_dismissed', '1'); } catch (e) {}
    });

    function check_notif_status() {
        if (/Mac/.test(navigator.platform)) { return; }
        if (!('Notification' in window)) {
            var dismissed_unsupported = false;
            try { dismissed_unsupported = localStorage.getItem('da_notif_denied_dismissed') === '1'; } catch (e) {}
            if (dismissed_unsupported) { return; }
            $('#da_notif_msg').text('Tu navegador no soporta notificaciones de escritorio. Las alertas de descuentos se mostrarán como mensajes en pantalla.');
            $('#da_notif_enable_btn').hide();
            $notif_banner.slideDown();
            return;
        }
        if (Notification.permission === 'denied') {
            var dismissed = false;
            try { dismissed = localStorage.getItem('da_notif_denied_dismissed') === '1'; } catch (e) {}
            if (dismissed) { return; }
            $('#da_notif_msg').text('Las notificaciones están bloqueadas. Para recibir alertas de descuentos, habilitálas en Configuración del sitio en tu navegador.');
            $('#da_notif_enable_btn').hide();
            $notif_banner.slideDown();
            return;
        }
        if (Notification.permission === 'default') {
            $('#da_notif_msg').text('Habilitá las notificaciones para recibir alertas de solicitudes de descuento.');
            $('#da_notif_enable_btn').show().off('click').on('click', function() {
                Notification.requestPermission().then(function(result) {
                    if (result === 'granted') {
                        $notif_banner.slideUp();
                    } else if (result === 'denied') {
                        check_notif_status();
                    }
                });
            });
            $notif_banner.slideDown();
        }
        // 'granted' → no banner
    }

    // Request notification permission on first user interaction
    function requestNotifPermission() {
        if (/Mac/.test(navigator.platform)) { return; }
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(function(result) {
                if (result === 'granted') $notif_banner.slideUp();
                else check_notif_status();
            });
        }
    }
    $(document).one('click keydown', requestNotifPermission);

    function notify(type, msg, link) {
        // Try browser notification first
        if ('Notification' in window && Notification.permission === 'granted') {
            var title = type === 'warning' ? '⚠️ Descuentos pendientes' : '🔖 Solicitud de descuento';
            var n = new Notification(title, {
                body: msg,
                icon: '<?= base_url('images/menubar/discount_approvals.svg') ?>',
                tag:  'discount-' + type   // collapse same-type notifs
            });
            if (link) n.onclick = function() { window.focus(); window.location.href = link; n.close(); };
            return;
        }

        // Fallback: toast
        var bg   = type === 'warning' ? '#e67e22' : '#2980b9';
        var icon = type === 'warning' ? 'bi-exclamation-triangle' : 'bi-tag';
        var $t = $('<div>')
            .css({background: bg, color: '#fff', borderRadius: '4px', padding: '10px 14px',
                  marginTop: '8px', boxShadow: '0 2px 8px rgba(0,0,0,.3)', fontSize: '13px',
                  cursor: 'pointer', position: 'relative'})
            .html('<span class="bi ' + icon + '"></span>&nbsp;' + msg +
                  (link ? ' <a href="' + link + '" style="color:#fff; text-decoration:underline; margin-left:8px;">Ver</a>' : '') +
                  '<span style="position:absolute;top:6px;right:10px;cursor:pointer;font-size:16px;" class="da_toast_close">&times;</span>');
        $t.find('.da_toast_close').on('click', function() { $t.remove(); });
        if (link) $t.on('click', function(e) { if (!$(e.target).is('.da_toast_close')) window.location.href = link; });
        $('#discount_toast_container').append($t);
        setTimeout(function() { $t.fadeOut(400, function() { $t.remove(); }); }, 8000);
    }

    function pollDiscountApprovals() {
        $.ajax({
            url: '<?= base_url('discount_approvals/pendingCount') ?>',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                var count   = res.count  || 0;
                var ids     = res.ids    || [];
                var approvalUrl = '<?= base_url('discount_approvals') ?>';

                // First poll: bootstrap silently — don't toast for pre-existing requests
                if (!_initialized) {
                    _knownIds    = ids;
                    _initialized = true;
                    return;
                }

                var newIds = ids.filter(function(id) { return _knownIds.indexOf(id) === -1; });
                newIds.forEach(function(id) {
                    notify('info', 'Nueva solicitud de descuento pendiente', approvalUrl);
                });
                _knownIds = ids;

                if (count >= 3 && !_warnedThreshold) {
                    _warnedThreshold = true;
                    notify('warning', 'Hay ' + count + ' solicitudes de descuento pendientes', approvalUrl);
                }
                if (count < 3) _warnedThreshold = false;

                // Update menubar badge
                var $badge = $('#discount_pending_badge');
                if (count > 0) {
                    if ($badge.length === 0) {
                        $badge = $('<span id="discount_pending_badge" class="badge" style="background:#d9534f; position:absolute; top:2px; right:2px; font-size:9px; min-width:16px; padding:2px 4px;">' + count + '</span>');
                        $('a[href*="discount_approvals"]').first().css('position', 'relative').append($badge);
                    } else {
                        $badge.text(count);
                    }
                } else {
                    $badge.remove();
                }
            }
        });
    }

    $(document).ready(function() {
        // Run immediately, then every 10s (faster on the approvals page)
        pollDiscountApprovals();
        var interval = window.location.href.indexOf('discount_approvals') !== -1 ? 3000 : 10000;
        setInterval(pollDiscountApprovals, interval);
    });
})();
</script>
<?php endif; ?>

