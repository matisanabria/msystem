<?php
/**
 * Module cards shared by home and office. Home groups them into zones (Module::HOME_GROUPS);
 * zones left empty by permissions are skipped, and modules outside any zone go in a last untitled one.
 *
 * @var array  $allowed_modules
 * @var object $user_info
 * @var string $grid_id
 * @var bool   $grouped
 */

use App\Models\Module;

$module_icons  = include APPPATH . 'Views/partial/module_icons.php';
$module_colors = include APPPATH . 'Views/partial/module_colors.php';

$visible = array_filter($allowed_modules, static fn ($m) => $m->module_id !== 'home');

if ($grouped) {
    $zones  = [];
    $placed = [];
    foreach (Module::HOME_GROUPS as $title_key => $ids) {
        $zone = array_values(array_filter($visible, static fn ($m) => in_array($m->module_id, $ids, true)));
        if ($zone) {
            $zones[lang("Module.$title_key")] = $zone;
            array_push($placed, ...array_column($zone, 'module_id'));
        }
    }
    $rest = array_values(array_filter($visible, static fn ($m) => !in_array($m->module_id, $placed, true)));
    if ($rest) {
        $zones[''] = $rest;
    }
} else {
    $zones = ['' => array_values($visible)];
}
?>

<?php if (!$grouped): ?>
    <div class="home-intro home-intro-section">
        <h1><span class="bi bi-<?= $module_icons['office'] ?>" aria-hidden="true"></span> <?= lang('Module.office') ?></h1>
        <p><?= lang('Common.office_subtitle') ?></p>
    </div>
<?php endif; ?>

<div id="<?= $grid_id ?>" class="module-zones<?= $grouped ? ' module-zones-home' : '' ?>">
    <?php foreach ($zones as $zone_title => $zone_modules): ?>
        <section class="module-zone">
            <?php if ($zone_title !== ''): ?>
                <h2 class="module-zone-title"><?= $zone_title ?></h2>
            <?php endif; ?>
            <div class="module-grid">
                <?php foreach ($zone_modules as $module): ?>
                    <a href="<?= base_url($module->module_id) ?>"
                       class="module-card<?= $module->module_id === 'sales' ? ' module-card-featured' : '' ?>"
                       style="--mc: <?= $module_colors[$module->module_id] ?? '#64748b' ?>"
                       title="<?= esc(lang("Module.{$module->module_id}_desc")) ?>">
                        <span class="module-card-icon">
                            <span class="bi bi-<?= $module_icons[$module->module_id] ?? 'app' ?>" aria-hidden="true"></span>
                        </span>
                        <span class="module-card-name"><?= lang("Module.{$module->module_id}") ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
