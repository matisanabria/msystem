<?php
/**
 * @var array $allowed_modules
 */
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    dialog_support.init("a.modal-dlg");
</script>

<?= view('home/module_grid', ['grid_id' => 'office_module_list', 'grouped' => false]) ?>

<?php if (model(\App\Models\Employee::class)->has_grant('changelog', (int)$user_info->person_id)): ?>
    <p class="text-end small mt-4 mb-0">
        <a href="<?= site_url('changelog') ?>" class="link-secondary text-decoration-none">
            <span class="bi bi-stars" aria-hidden="true"></span> <?= lang('Changelog.link') ?>
        </a>
    </p>
<?php endif; ?>

<?= view('partial/footer') ?>
