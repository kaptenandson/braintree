/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

'use strict';

var $ = require('jquery');
var braintree = require('braintree-web');

function init(config) {
    var $form = $(config.formSelector);
    var $errorContainers = $('.braintree-error');
    var $braintreeContanier = $('.braintree-method:first');
    var braintreeClientToken = !!$braintreeContanier.length ? ($braintreeContanier.data('braintree-client-token') || null) : null;

    function getCurrentPaymentMethod() {
        return $(config.currentPaymentMethodSelector).val();
    }

    function getErrorTemplate(message) {
        return '<ul class="form-errors"><li>' + message + '</li></ul>'
    }

    function submitForm(nonce){
        $form.find('input[name="' + config.nonceInputName + '"]').val(nonce || '');
        $form.submit();
    }

    function errorHandler(error) {
        var paymentMethod = getCurrentPaymentMethod();
        var isPayPal = (paymentMethod === 'braintreePayPal');
        var isCreditCard = (paymentMethod === 'braintreeCreditCard');

        $errorContainers.empty();

        if (isPayPal) {
            return $('.braintree-paypal-error').html(getErrorTemplate(error.message));
        }

        if (isCreditCard) {
            return $('.braintree-credit-card-error').html(getErrorTemplate(error.message));
        }

        return submitForm();
    }

    function paymentMethodHandler(response) {
        var paymentMethod = getCurrentPaymentMethod();
        var isWrongMethodSelected = (paymentMethod === 'braintreePayPal' && response.type !== 'PayPalAccount') || (paymentMethod === 'braintreeCreditCard' && response.type !== 'CreditCard');

        $errorContainers.empty();

        if (isWrongMethodSelected) {
            return errorHandler({
                message: 'User did not enter a payment method'
            });
        }

        return submitForm(response.nonce);
    }

    function readyHandler() {
        $form.append('<input type="hidden" name="' + config.nonceInputName + '">');

        $(config.paymentMethodSelector).on('change', function() {
            $form.find('input[name="' + config.nonceInputName + '"]').val('');
            $errorContainers.empty();
        });

        $('.braintree-loader').removeClass('show');
        $('.braintree-method').addClass('show');
    }

    function loadBraintree() {
        var braintreeSetupSettings = {
            onReady: readyHandler,
            onPaymentMethodReceived: paymentMethodHandler,
            onError: errorHandler
        };

        if ($('.braintree-credit-card-method').length) {
            braintreeSetupSettings.id = config.formSelector.replace('#', '');
            braintreeSetupSettings.hostedFields = {
                styles: {
                    'input': {
                        'font-size': '16px',
                        'color': '#333',
                        'font-family': 'Fira Sans, Arial, sans-serif'
                    }
                },
                number: {
                    selector: '#braintree-credit-card-number',
                    placeholder: '4111 1111 1111 1111'
                },
                cvv: {
                    selector: '#braintree-credit-card-cvv',
                    placeholder: '123'
                },
                expirationDate: {
                    selector: '#braintree-credit-card-expiration-date',
                    placeholder: 'MM/YYYY'
                }
            };
        }

        if ($('.braintree-paypal-method').length) {
            braintreeSetupSettings.paypal = {
                container: 'braintree-paypal-container'
            };
        }

        braintree.setup(braintreeClientToken, 'custom', braintreeSetupSettings);
    }

    if (!!braintreeClientToken) {
        loadBraintree();
    }
}

module.exports = {
    init: init
};
