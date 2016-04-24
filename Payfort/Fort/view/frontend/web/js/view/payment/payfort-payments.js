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
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'payfort_fort_cc',
                component: 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_cc-method'
            },
            {
                type: 'payfort_fort_sadad',
                component: 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_sadad-method'
            },
            {
                type: 'payfort_fort_naps',
                component: 'Payfort_Fort/js/view/payment/method-renderer/payfort_fort_naps-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);