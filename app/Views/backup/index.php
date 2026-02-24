<?php
/**
 * @var array $backups
 */
?>

<?= view('partial/header') ?>

<?php if (session()->getFlashdata('message')) : ?>
    <div class="alert alert-success alert-dismissible">
        <?= esc(session()->getFlashdata('message')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger alert-dismissible">
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <h3><?= lang('Backup.heading') ?></h3>
        <p class="text-muted"><?= lang('Backup.info') ?></p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <form method="POST" action="<?= site_url('backup/create') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-download-alt"></span>
                <?= lang('Backup.create_backup') ?>
            </button>
        </form>
    </div>
</div>

<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <?php if (empty($backups)) : ?>
            <p class="text-muted"><?= lang('Backup.no_backups') ?></p>
        <?php else : ?>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= lang('Backup.file') ?></th>
                        <th><?= lang('Backup.date') ?></th>
                        <th><?= lang('Backup.size') ?></th>
                        <th><?= lang('Backup.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup) : ?>
                        <tr>
                            <td><?= esc($backup['filename']) ?></td>
                            <td><?= esc($backup['date']) ?></td>
                            <td><?= esc($backup['size']) ?></td>
                            <td>
                                <a href="<?= site_url('backup/download/' . urlencode($backup['filename'])) ?>"
                                   class="btn btn-sm btn-default">
                                    <span class="glyphicon glyphicon-download-alt"></span>
                                    <?= lang('Backup.download') ?>
                                </a>
                                <form method="POST" action="<?= site_url('backup/delete') ?>"
                                      style="display:inline-block;"
                                      onsubmit="return confirm('Delete <?= esc($backup['filename'], 'js') ?>?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="filename" value="<?= esc($backup['filename']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <span class="glyphicon glyphicon-trash"></span>
                                        <?= lang('Backup.delete') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= view('partial/footer') ?>
