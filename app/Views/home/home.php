<?php
/**
 * @var array $allowed_modules
 */
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    dialog_support.init("a.modal-dlg");
</script>

<?= view('home/module_grid', ['grid_id' => 'home_module_list', 'grouped' => true]) ?>

<?= view('partial/footer') ?>
