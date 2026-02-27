<?php
/**
 * @var object $person_info
 * @var array $config
 */
?>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.first_name'), 'first_name', ['class' => 'required control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'first_name',
            'id'    => 'first_name',
            'class' => 'form-control input-sm',
            'value' => $person_info->first_name
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.last_name'), 'last_name', ['class' => 'required control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'last_name',
            'id'    => 'last_name',
            'class' => 'form-control input-sm',
            'value' => $person_info->last_name
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.email'), 'email', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <div class="input-group">
            <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-envelope"></span></span>
            <?= form_input([
                'name'  => 'email',
                'id'    => 'email',
                'class' => 'form-control input-sm',
                'value' => $person_info->email
            ]) ?>
        </div>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.phone_number'), 'phone_number', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <div class="input-group">
            <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-phone-alt"></span></span>
            <?= form_input([
                'name'  => 'phone_number',
                'id'    => 'phone_number',
                'class' => 'form-control input-sm',
                'value' => $person_info->phone_number
            ]) ?>
        </div>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.address_1'), 'address_1', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'address_1',
            'id'    => 'address_1',
            'class' => 'form-control input-sm',
            'value' => $person_info->address_1
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.city'), 'city', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'city',
            'id'    => 'city',
            'class' => 'form-control input-sm',
            'value' => $person_info->city
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.identification_type'), 'identification_type', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_dropdown('identification_type', [
            ''         => lang('Common.select_id_type'),
            'CI'       => lang('Common.id_type_ci'),
            'RUC'      => lang('Common.id_type_ruc'),
            'DNI'      => lang('Common.id_type_dni'),
            'PASAPORTE'=> lang('Common.id_type_passport'),
        ], $person_info->identification_type ?? '', ['id' => 'identification_type', 'class' => 'form-control input-sm']) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.identification'), 'identification', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'identification',
            'id'    => 'identification',
            'class' => 'form-control input-sm',
            'value' => $person_info->identification ?? ''
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.country'), 'country', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'country',
            'id'    => 'country',
            'class' => 'form-control input-sm',
            'value' => $person_info->country
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Common.comments'), 'comments', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_textarea([
            'name'  => 'comments',
            'id'    => 'comments',
            'class' => 'form-control input-sm',
            'value' => $person_info->comments
        ]) ?>
    </div>
</div>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        nominatim.init({
            fields: {
                city: {
                    dependencies: ["city", "country"],
                    response: {
                        format: ["village|town|hamlet|city_district|city", "country"]
                    }
                },

                country: {
                    dependencies: ["country"]
                }
            },
            language: '<?= current_language_code() ?>',
            country_codes: '<?= esc($config['country_codes'], 'js') ?>'
        });
    });
</script>
