jQuery(function ($) {

    function zash_selectWoo(element) {
        let select2_args = {
            placeholder: element.attr('data-placeholder') || element.attr('placeholder') || '',
            width: '100%'
        };

        element.selectWoo(select2_args);
    }

    function zash_state_changed(type, state_id) {

        let city_element = $('select#' + type + '_city');

        city_element.html('<option value="0">در حال بارگزاری لیست شهرها...</option>');

        let data = {
            'action': 'zagma_load_cities',
            'state_id': state_id,
            'type': type
        };

        $.post(zash_settings.ajax_url, data, function (response) {
            city_element.html(response);
        });

        zash_selectWoo(city_element);
    }

    $("select[id$='_state']").on('select2:select', function (e) {
        let type = $(this).attr('id').indexOf('billing') !== -1 ? 'billing' : 'shipping';
        let data = e.params.data;
        zash_state_changed(type, data.id);
    });

    $("select[id$='_city']").on('select2:select', function (e) {
        $('body').trigger('update_checkout');
    });

    zash_settings.types.forEach(type => {
        zash_selectWoo($('select#' + type + '_state'));
        zash_selectWoo($('select#' + type + '_city'));
    });

    let is_cod = zash_settings.is_cod;

    $('form.checkout').on('change', 'input[name^="payment_method"]', function () {
        if (($(this).val() === 'cod') !== is_cod) {
            $('body').trigger('update_checkout');
            is_cod = ($(this).val() === 'cod');
        }
    });

});