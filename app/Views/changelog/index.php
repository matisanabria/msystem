<?php
/**
 * @var array<string, list<array{date: string, type: string, title: string, text: string}>> $months  keyed by YYYY-MM
 */

$badge = ['nuevo' => 'text-bg-success', 'mejora' => 'text-bg-primary', 'arreglo' => 'text-bg-secondary'];
?>
<?= view('partial/header') ?>

<style>
    #changelog { max-width: 720px; }
    #changelog .cl-month { font-size: 1rem; font-weight: 700; margin: 24px 0 8px; text-transform: capitalize; }
    #changelog .cl-entry { padding: 12px 0; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
    #changelog .cl-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    #changelog .cl-date { color: var(--bs-secondary-color, #6c757d); font-size: .875rem; }
    #changelog .cl-title { font-weight: 600; }
    #changelog .cl-text { margin: 4px 0 0; }
    #changelog .cl-items { margin: 6px 0 0; padding-left: 1.25rem; }
    #changelog .cl-items li { margin-bottom: 2px; }
</style>

<div id="changelog">
    <div id="title_bar" class="btn-toolbar print_hide">
        <a href="<?= site_url('office') ?>" class="btn btn-outline-secondary btn-sm">
            <span class="bi bi-arrow-left"></span>&nbsp;<?= lang('Changelog.back') ?>
        </a>
        <h4 class="float-start" style="margin: 4px 15px 0;">
            <span class="bi bi-stars"></span>&nbsp;<?= lang('Changelog.title') ?>
        </h4>
    </div>

    <?php if (empty($months)): ?>
        <p class="text-muted mt-3"><?= lang('Changelog.empty') ?></p>
    <?php endif; ?>

    <?php foreach ($months as $ym => $entries): ?>
        <?php [$year, $month] = explode('-', $ym); ?>
        <h2 class="cl-month"><?= lang('Changelog.month_' . $month) ?> <?= esc($year) ?></h2>
        <?php foreach ($entries as $entry): ?>
            <div class="cl-entry">
                <div class="cl-head">
                    <span class="cl-date"><?= esc(date('d/m/Y', strtotime($entry['date']))) ?></span>
                    <span class="badge <?= $badge[$entry['type']] ?? 'text-bg-secondary' ?>"><?= lang('Changelog.type_' . $entry['type']) ?></span>
                    <span class="cl-title"><?= esc($entry['title']) ?></span>
                </div>
                <p class="cl-text"><?= esc($entry['text']) ?></p>
                <?php if (!empty($entry['items'])): ?>
                    <ul class="cl-items">
                        <?php foreach ($entry['items'] as $item): ?>
                            <li><?= esc($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<?= view('partial/footer') ?>
