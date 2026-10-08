<?php
/**
 * Shared item photo viewer. Any <button class="item-photo-btn" data-photo-src data-photo-name> opens it.
 * Used by items/manage.php and sales/register.php.
 */
?>
<div class="modal fade" id="item-photo-modal" tabindex="-1" aria-labelledby="item-photo-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="item-photo-title"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= lang('Items.close_photo') ?>"></button>
            </div>
            <div class="modal-body text-center">
                <img class="item-photo-large" src="" alt="">
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Focus goes back to the thumbnail that opened the viewer
    $(document).on('click', '.item-photo-btn', function() {
        var $btn = $(this);
        var $modal = $('#item-photo-modal');
        $modal.find('.modal-title').text($btn.data('photo-name'));
        $modal.find('img').attr({src: $btn.data('photo-src'), alt: $btn.data('photo-name')});
        $modal.data('trigger', $btn[0]);
        window.registerFocusReturn = $btn[0]; // the sales register otherwise sends focus to its search box
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });
    $(document).on('hidden.bs.modal', '#item-photo-modal', function() {
        var trigger = $(this).data('trigger');
        $(this).find('img').attr('src', '');
        trigger && document.body.contains(trigger) && trigger.focus();
    });
</script>
