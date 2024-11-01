jQuery(document).ready(function ($) {

    let zash_IDs = [];
    let zash_button_submit = $("#zash-zagma-submit");
    let zash_button_ship = $("#zash-zagma-ship");

    zash_button_submit.click(function () {
        zash_change_status('zash-packaged');
    });

    zash_button_ship.click(function () {
        zash_change_status('zash-readyto-ship');
    });

    function zash_change_status(status) {

        zash_IDs = [];

        $('.check-column input[name="post[]"]:checked').each(function () {
            zash_IDs.push($(this).val());
        });

        if (zash_IDs.length === 0) {
            alert('سفارشی جهت پردازش انتخاب نشده است.');
            return false;
        }

        // Start
        zash_button_submit.attr('disabled', 'disabled');
        zash_button_ship.attr('disabled', 'disabled');
        $('.zash-tips').remove();

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

        $("tr#post-" + id + " td.order_status").html(`
                        <mark class="order-status">
                            <span>...</span>
                        </mark>
                    `);

        $.post(ajaxurl, data).then(function (response) {

            response = JSON.parse(response);

            if (response.success) {

                $("tr#post-" + id + " td.order_status").html(`
                                <mark class="order-status status-processing">
                                    <span>${response.message}</span>
                                </mark>
                            `);

            } else {

                $("tr#post-" + id + " td.order_status").html(`
                                <mark class="order-status status-zash-returned">
                                    <span>خطا در پردازش</span>
                                </mark>
                            `);

                $("tr#post-" + id + " td.column-order_number").append(`
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