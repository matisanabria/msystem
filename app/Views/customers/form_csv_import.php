<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open_multipart('customers/importCsvFile/', ['id' => 'csv_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="item_basic_info">

        <div class="form-group form-group-sm">
            <div class="col-12">
                <a href="<?= esc('customers/csv') ?>"><?= lang('Common.download_import_template') ?></a>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <div class="col-12">
                <input type="file" class="form-control form-control-sm" id="file_path" name="file_path" accept=".csv">
            </div>
        </div>

    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        $('#csv_form').validate($.extend({
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?= esc('customers') ?>', response);
                    },
                    dataType: 'json'
                });
            },

            errorLabelContainer: '#error_message_box',

            rules: {
                file_path: 'required'
            },

            messages: {
                file_path: "<?= lang('Common.import_full_path') ?>"
            }
        }, form_support.error));
    });
</script>
