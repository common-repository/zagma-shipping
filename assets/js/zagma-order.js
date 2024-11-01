jQuery(document).ready(function ($) {

    $('#shipping_state_city').selectWoo();

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    let zash_IDs = [urlParams.get('post')];
    let zash_button_submit = $("#zash-zagma-submit");
    let zash_button_ship = $("#zash-zagma-ship");

    zash_button_submit.click(function () {
        zash_change_status('zash-packaged');
    });

    zash_button_ship.click(function () {
        zash_change_status('zash-readyto-ship');
    });

    $('#zagma_weight').keydown(function () {
        zash_button_submit.hide();
        $('#zash-zagma-submit-tip').show();
    });

    $('#shipping_state_city').change(function () {
        zash_button_submit.hide();
        $('#zash-zagma-submit-tip').show();
    });

    function zash_change_status(status) {

        // Start
        zash_button_submit.attr('disabled', 'disabled');
        zash_button_ship.attr('disabled', 'disabled');
        $('.zash-tips').html('');

        zash_change_status_ajax(status);
    }

    function zash_change_status_ajax(status) {

        let id = zash_IDs.shift();

        if (id == undefined) {
            // End
            zash_button_submit.removeAttr('disabled');
            zash_button_ship.removeAttr('disabled');
            return true;
        }

        let data = {
            'action': 'zash_change_order_status',
            'status': status,
            'id': id
        };

        $(".zash-tips").html(`
                        <mark class="order-status">
                            <span>...</span>
                        </mark>
                    `);

        $.post(ajaxurl, data).then(function (response) {

            response = JSON.parse(response);

            if (response.success) {

                $(".zash-tips").html(`
                                <mark class="order-status status-processing">
                                    <span>${response.message}</span>
                                </mark>
                            `);

                setTimeout(function () {
                    location.reload();
                }, 3000);

            } else {

                $(".zash-tips").html(`
                                <mark class="order-status status-zash-returned">
                                    <span>خطا در پردازش</span>
                                </mark>
                            `);

                $(".zash-tips").append(`
                                <mark class="order-status status-zash-returned zash-tips"
                                        style="margin-top: 10px; font-size: 11px;">
                                    <span>
                                        ${response.message}
                                    </span>
                                </mark>
                            `);

            }

            zash_change_status_ajax(status);
        });

    }
});