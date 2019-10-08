// Add functionality to Bolder Fees optional fees checkbox
jQuery(document).ready(function($) {

    // trigger summary update when user checks/unchecks the fee box (cart page)
    jQuery(".cart-collaterals").on( 'change', '.cart_totals .fee_optional_box', function () {
        // Check for options that are checked
        fList = {};
        jQuery('.cart_totals table input[type=checkbox]').each(function () {
            var sel = false;
            var feeName = jQuery(this).attr('title');
            if(jQuery(this).is(':checked')) { sel = true; }
            if(typeof feeName === 'undefined') { feeName = be_fees_data.text_additional_fees; }
            fList[jQuery(this).attr('name')] = { 'fee': jQuery(this).val(), 'name': feeName, 'selected': sel }
        });

        var t = jQuery(this).val();
        var s = jQuery(this).is(':checked');
        jQuery("div.cart_totals").block({
            message: null,
            overlayCSS: {
                background: "#fff",
                backgroundSize: "16px 16px",
                opacity: .6
            }
        });
        var n = {
            action: "bolder_update_shipping_fees",
            security: wc_cart_params.update_shipping_method_nonce,
            fees: fList,
        };
        jQuery.post(wc_cart_params.ajax_url, n, function (t) {
            jQuery("div.cart_totals").replaceWith(t);
        })
    });

    // trigger totals update when payment gateway is changed to support payment gateway condition
    jQuery("form.checkout").on("change", "input[name=payment_method]", function () {
        n = !1;
        jQuery("body").trigger("update_checkout")
    });

    // trigger update when optional fee box is changed (checkout page)
    jQuery("form.checkout div#order_review").on("change", "input.fee_optional_box", function () {

        // Check for options that are checked
        fList = {};
        var t = jQuery(this).val();
        var s = jQuery(this).is(':checked');
        jQuery('form.checkout .fee-optional input').each(function () {
            var sel = false;
            var feeName = jQuery(this).attr('title');
            if(jQuery(this).is(':checked')) { sel = true; }
            if(typeof feeName === 'undefined') { feeName = be_fees_data.text_additional_fees; }
            fList[jQuery(this).attr('name')] = { 'fee': jQuery(this).val(), 'name': feeName, 'selected': sel }
        });

        var n = {
            action: "bolder_update_checkout_fees",
            fees: fList,
        };
        jQuery.post(wc_checkout_params.ajax_url, n, function (t) {
            jQuery("body").trigger("update_checkout");
        });
    });

});
