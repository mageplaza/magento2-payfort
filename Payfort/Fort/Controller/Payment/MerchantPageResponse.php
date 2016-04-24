<?php

namespace Payfort\Fort\Controller\Payment;

class MerchantPageResponse extends \Payfort\Fort\Controller\Checkout
{
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('merchant_reference');
        $order = $this->getOrderById($orderId);
        $returnUrl = $this->getHelper()->getUrl('checkout/onepage/success');
        $responseParams = $this->getRequest()->getParams();
        $paymentResponse = $this->getHelper()->validateResponse($responseParams);
        $paymentModel = $this->getPayfortModel();
        $success = true;
        if($paymentResponse == $paymentModel::PAYMENT_STATUS_SUCCESS) {
            $host2HostParams = $this->getHelper()->merchantPageNotifyFort($order, $responseParams);
            $paymentResponseH2h = $this->getHelper()->validateResponse($host2HostParams);
            if($paymentResponseH2h == $paymentModel::PAYMENT_STATUS_3DS_CHECK && isset($host2HostParams['3ds_url'])) {
                //$this->getResponse()->setRedirect($host2HostParams['3ds_url']);
                header('location:'.$host2HostParams['3ds_url']);
                exit;
            }
            else{
                $success = false;
            }
        }
        else {
            $success = false;
        }
        
        if($success) {
            $this->getHelper()->processOrder($order);
            $returnUrl = $this->getHelper()->getUrl('checkout/onepage/success');
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
