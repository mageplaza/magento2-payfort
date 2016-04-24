<?php

namespace Payfort\Fort\Controller\Payment;

class Response extends \Payfort\Fort\Controller\Checkout
{
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('merchant_reference');
        $order = $this->getOrderById($orderId);
        $returnUrl = $this->getHelper()->getUrl('checkout/onepage/success');
        
        $responseParams = $this->getRequest()->getParams();
        $paymentResponse = $this->getHelper()->validateResponse($responseParams);
        $paymentModel = $this->getPayfortModel();
        if($paymentResponse == $paymentModel::PAYMENT_STATUS_SUCCESS) {
            $this->getHelper()->processOrder($order);
            $returnUrl = $this->getHelper()->getUrl('checkout/onepage/success');
        }
        elseif($paymentResponse == $paymentModel::PAYMENT_STATUS_CANCELED) {
            $this->_cancelPayment($order, 'User has cancel the payment');
            $this->_checkoutSession->restoreQuote();
            $message = __('You have canceled the payment.');
            $this->messageManager->addError( $message );            
            $returnUrl = $this->getHelper()->getUrl('checkout/cart');
        }
        else {
            $this->getHelper()->orderFailed($order);
            $this->_checkoutSession->restoreQuote();
            
            $message = __('Error: Payment Failed, Please check your payment details and try again.');
            $this->messageManager->addError( $message );
            $returnUrl = $this->getHelper()->getUrl('checkout/cart');
        }

        $this->orderRedirect($order, $returnUrl);
    }

}
