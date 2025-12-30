define([
    'Magento_Checkout/js/view/payment/default',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/checkout-data'
], function (Component, url, fullScreenLoader, selectPaymentMethodAction, checkoutData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Caferobot_Mellat/payment/mellat',
            redirectAfterPlaceOrder: false
        },

        /**
         * The HTML calls "click: selectPaymentMethod"
         */
        selectPaymentMethod: function () {
            selectPaymentMethodAction(this.getData());
            checkoutData.setSelectedPaymentMethod(this.item.method);
            return true;
        },

        /**
         * The HTML calls "html: getInstructions()"
         */
        getInstructions: function () {
            if (window.checkoutConfig.payment.mellat) {
                return window.checkoutConfig.payment.mellat.instructions;
            }
            return '';
        },

        /**
         * This handles the actual redirect after the order is saved
         */
        afterPlaceOrder: function () {
            fullScreenLoader.startLoader();
            window.location.replace(url.build('mellat/index/start'));
        }
    });
});