/*
 * Copyright (c) 2026 WebWorks 101 OÜ (MageBooster). All rights reserved.
 * Commercial licence, see LICENSE.txt. https://magebooster.com
 * Licensed per serial number for one production installation (one store domain); staging and
 * development copies of that store are allowed. Serial numbers and downloads are tied to the customer.
 * You may modify this code for your own licensed store; keep this notice.
 * Not allowed: copying to other stores, redistributing, reselling, sublicensing, publishing it publicly,
 * removing licence checks or notices, sharing serial numbers. Unauthorised use: the serial number is
 * revoked without refund, updates and support stop, damages and legal action may follow.
 * Estonian law applies; court: Harju County Court (Harju Maakohus).
 */
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
