define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'magebooster_rubik',
        component: 'MageBooster_Rubik/js/view/payment/method-renderer/magebooster_rubik'
    });

    return Component.extend({});
});
