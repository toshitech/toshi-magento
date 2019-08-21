define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        '../model/shipping-rates-validator/toshi',
        '../model/shipping-rates-validation-rules/toshi'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('toshi', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('toshi', shippingRatesValidationRules);
        return Component;
    }
);