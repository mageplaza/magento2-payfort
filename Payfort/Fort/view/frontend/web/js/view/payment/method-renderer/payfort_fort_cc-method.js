/**
 * Payfot_Fort Magento JS component
 *
 * @category    Payfort
 * @package     Payfot_Fort
 * @author      Deya Zalloum
 * @copyright   Payfort (http://www.payfort.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
    ],
    function ($, Component, quote, fullScreenLoader, setPaymentInformationAction, placeOrder) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Payfort_Fort/payment/payfort-form'
            },
            getCode: function() {
                return 'payfort_fort_cc';
            },
            isActive: function() {
                return true;
            },
            context: function() {
                return this;
            },
            getInstructions: function() {
                return window.checkoutConfig.payment.payfortFort.payfort_fort_cc.instructions;
            },
            // Overwrite properties / functions
            redirectAfterPlaceOrder: false,
            
            /**
             * @override
             */
            placeOrder: function () {

                var self = this;
                var paymentData = quote.paymentMethod();
                var messageContainer = this.messageContainer;
                fullScreenLoader.startLoader();
                this.isPlaceOrderActionAllowed(false);
                $.when(setPaymentInformationAction(this.messageContainer, {
                    'method': self.getCode()
                })).done(function () {
                        $.when(placeOrder(paymentData, messageContainer)).done(function () {
                            $.mage.redirect(window.checkoutConfig.payment.payfortFort.payfort_fort_cc.redirectUrl);
                        });
                }).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).always(function(){
                    fullScreenLoader.stopLoader();
                });
            }
        });
    }
);