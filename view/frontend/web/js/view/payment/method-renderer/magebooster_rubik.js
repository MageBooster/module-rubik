/**
 * Standard payment renderer (Magento_Checkout/js/view/payment/default), so it works in the native checkout and in
 * one-step checkouts that render standard payment methods. After the order is placed the customer goes to
 * mbrubik/redirect/start, which sends them on to Rubikpara.
 */
define([
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'Magento_Checkout/js/model/full-screen-loader'
], function (ko, Component, quote, url, fullScreenLoader) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MageBooster_Rubik/payment/magebooster_rubik',
            redirectAfterPlaceOrder: false
        },

        getConfig: function () {
            return (window.checkoutConfig.payment || {})['magebooster_rubik'] || {};
        },

        getDescription: function () {
            return this.getConfig().description || '';
        },

        isTestMode: function () {
            return !!this.getConfig().testMode;
        },

        /** TRY amount shown under the method when the store currency isn't charged directly. */
        tryAmountText: ko.pureComputed(function () {
            var config = (window.checkoutConfig.payment || {})['magebooster_rubik'] || {},
                totals = quote.totals(),
                amount;

            if (!config.convertToTry || !config.tryRate || !totals) {
                return '';
            }
            amount = (parseFloat(totals.base_grand_total) * parseFloat(config.tryRate)).toFixed(2);
            return amount + ' TRY (1 ' + config.baseCurrency + ' = ' + parseFloat(config.tryRate).toFixed(4) + ' TRY)';
        }),

        afterPlaceOrder: function () {
            fullScreenLoader.startLoader();
            window.location.replace(url.build('mbrubik/redirect/start'));
        }
    });
});
