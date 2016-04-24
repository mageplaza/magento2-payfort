<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payfort\Fort\Helper;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
/**
 * Payment module base helper
 */
class Data extends \Magento\Payment\Helper\Data
{
    protected $_code;
    private $_gatewayHost        = 'https://checkout.payfort.com/';
    private $_gatewaySandboxHost = 'https://sbcheckout.payfort.com/';
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    
    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    /**
     *
     * @var type 
     */
    protected $_checkoutSession;
    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface 
     */
    protected $_storeManager;
    
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;
    
    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;
    
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $session,
        OrderManagementInterface $orderManagement,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        parent::__construct($context,$layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_storeManager = $storeManager;
        $this->session = $session;
        $this->_logger = $logger;
        $this->_localeResolver = $localeResolver;
        $this->orderManagement = $orderManagement;
        $this->_objectManager = $objectManager;
    }
    
    public function setMethodCode($code) {
        $this->_code = $code;
    }
    
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getMainConfigData($config_field)
    {
        return $this->scopeConfig->getValue(
            ('payment/payfort_fort/'.$config_field),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getPaymentPageRedirectData($order) {
        $paymentMethod = $order->getPayment()->getMethod();
        $orderId = $order->getRealOrderId();
        $currency = $order->getOrderCurrency()->getCurrencyCode();
        $amount = $this->convertFortAmount($order->getGrandTotal(), $currency);
        $language = $this->getLanguage();
        $gatewayParams = array(
            'amount'              => $amount,
            'currency'            => strtoupper($currency),
            'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
            'access_code'         => $this->getMainConfigData('access_code'),
            'merchant_reference'  => $orderId,
            'customer_email'      => trim( $order->getCustomerEmail() ),
            'command'             => $this->getMainConfigData('command'),
            'language'            => $language,
            'return_url'          => $this->getReturnUrl('payfortfort/payment/response')
        );
        if($paymentMethod == \Payfort\Fort\Model\Method\Sadad::CODE) {
            $gatewayParams['payment_option'] = 'SADAD';
        }
        elseif ($paymentMethod == \Payfort\Fort\Model\Method\Naps::CODE)
        {
            $gatewayParams['payment_option']    = 'NAPS';
            $gatewayParams['order_description'] = $orderId;
        }
        $gatewayParams['signature'] = $this->calculateSignature($gatewayParams, 'request');
        $gatewayUrl = $this->getGatewayUrl('redirection');
        
        $debugMsg = "Fort Redirect Request Parameters \n".print_r($gatewayParams, 1);
        $this->log($debugMsg);
        return array('url' => $gatewayUrl, 'params' => $gatewayParams);
    }
    
    public function getOrderCustomerName($order) {
        $customerName = '';
        if( $order->getCustomerId() === null ){
            $customerName = $order->getBillingAddress()->getFirstname(). ' ' . $order->getBillingAddress()->getLastname();
        }
        else{
            $customerName =  $order->getCustomerName();
        }
        return trim($customerName);
    }
    public function getMerchantPageData($order) {
            $language = $this->getLanguage();
            $orderId = $order->getRealOrderId();
            $gatewayParams = array(
                'merchant_identifier' => $this->getMainConfigData('merchant_identifier'),
                'access_code'         => $this->getMainConfigData('access_code'),
                'merchant_reference'  => $orderId,
                'service_command'     => 'TOKENIZATION',
                'language'            => $language,
                'return_url'          => $this->getReturnUrl('payfortfort/payment/merchantPageResponse'),
            );
            //calculate request signature
            $signature = $this->calculateSignature($gatewayParams, 'request');
            $gatewayParams['signature'] = $signature;
            
            $gatewayUrl = $this->getGatewayUrl();
            
            $debugMsg = "Fort Merchant Page Request Parameters \n".print_r($gatewayParams, true);
            $this->log($debugMsg);
        
            return array('url' => $gatewayUrl, 'params' => $gatewayParams);
    }
    
    public function isMerchantPageMethod($order) {
        $paymentMethod = $order->getPayment()->getMethod();
        if($paymentMethod == \Payfort\Fort\Model\Method\Cc::CODE && $this->getConfig('payment/payfort_fort_cc/integration_type') == \Payfort\Fort\Model\Config\Source\Integrationtypeoptions::MERCHANT_PAGE) {
            return true;
        }
        return false;
    }
    
    public function merchantPageNotifyFort($order, $fortParams) {
        //send host to host
        $language = $this->getLanguage();
        $orderId = $order->getRealOrderId();

        $return_url = $this->getReturnUrl('payfortfort/payment/response');

        $ip = $this->getVisitorIp();
        $currency = $order->getOrderCurrency()->getCurrencyCode();
        $amount = $this->convertFortAmount($order->getGrandTotal(), $currency);
        $postData = array(
            'merchant_reference'    => $orderId,
            'access_code'           => $this->getMainConfigData('access_code'),
            'command'               => $this->getMainConfigData('command'),
            'merchant_identifier'   => $this->getMainConfigData('merchant_identifier'),
            'customer_ip'           => $ip,
            'amount'                => $amount,
            'currency'              => strtoupper($currency),
            'customer_email'        => trim( $order->getCustomerEmail() ),
            'token_name'            => $fortParams['token_name'],
            'language'              => $language,
            'return_url'            => $return_url,
        );
        $customer_name = $this->getOrderCustomerName($order);
        if(!empty($customer_name)) {
            $postData['customer_name'] = $customer_name;
        }
        //calculate request signature
        $signature = $this->calculateSignature($postData, 'request');
        $postData['signature'] = $signature;
        
        $debugMsg = "Fort Merchant Page Notifiaction Request Parameters \n".print_r($postData, true);
        $this->log($debugMsg);
        
        $gatewayUrl = $this->getGatewayUrl('notificationApi');
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json;charset=UTF-8',
                //'Accept: application/json, application/*+json',
                //'Connection:keep-alive'
        ));
        curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_ENCODING, "compress, gzip");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects		
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // The number of seconds to wait while trying to connect
        //curl_setopt($ch, CURLOPT_TIMEOUT, Yii::app()->params['apiCallTimeout']); // timeout in seconds
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);

        //$response_data = array();

        //parse_str($response, $response_data);
        curl_close($ch);

        $array_result    = json_decode($response, true);

        $debugMsg = 'Fort Merchant Page Notifiaction Response Parameters'."\n".print_r($array_result, true);
        $this->log($debugMsg);
            
        if(!$response || empty($array_result)) {
            return false;
        }
        return $array_result;
    }

    /** @return string */
    function getVisitorIp() {
            /** @var \Magento\Framework\ObjectManagerInterface $om */
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $a */
            $a = $om->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
            return $a->getRemoteAddress();
    }

    /**
     * calculate fort signature
     * @param array $arr_data
     * @param sting $sign_type request or response
     * @return string fort signature
     */
    public function calculateSignature($arr_data, $sign_type = 'request')
    {
        $sha_in_pass_phrase  = $this->getMainConfigData('sha_in_pass_phrase');
        $sha_out_pass_phrase = $this->getMainConfigData('sha_out_pass_phrase');
        $sha_type = $this->getMainConfigData('sha_type');
        $sha_type = str_replace('-', '', $sha_type);
        
        $shaString = '';

        ksort($arr_data);
        foreach ($arr_data as $k => $v) {
            $shaString .= "$k=$v";
        }

        if ($sign_type == 'request') {
            $shaString = $sha_in_pass_phrase . $shaString . $sha_in_pass_phrase;
        }
        else {
            $shaString = $sha_out_pass_phrase . $shaString . $sha_out_pass_phrase;
        }
        $signature = hash($sha_type, $shaString);

        return $signature;
    }
    
    /**
     * Convert Amount with dicemal points
     * @param decimal $amount
     * @param string $baseCurrencyCode
     * @param string  $currentCurrencyCode
     * @return decimal
     */
    public function convertFortAmount($amount, $currencyCode)
    {

        $new_amount     = 0;
        $decimal_points = $this->getCurrencyDecimalPoint($currencyCode);
        $new_amount     = round($amount, $decimal_points);
        $new_amount     = $new_amount * (pow(10, $decimal_points));
        return $new_amount;
    }
    
    /**
     * 
     * @param string $currency
     * @param integer 
     */
    public function getCurrencyDecimalPoint($currency)
    {
        $decimalPoint  = 2;
        $arrCurrencies = array(
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
        );
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }
    
    public function getGatewayUrl($type='redirection') {
        $testMode = $this->getMainConfigData('sandbox_mode');
        if($type == 'notificationApi') {
            $gatewayUrl = $testMode ? $this->_gatewaySandboxHost.'FortAPI/paymentApi' : $this->_gatewayHost.'FortAPI/paymentApi';
        }
        else{
            $gatewayUrl = $testMode ? $this->_gatewaySandboxHost.'FortAPI/paymentPage' : $this->_gatewayHost.'FortAPI/paymentPage';
        }
        
        return $gatewayUrl;
    }
    
    public function getReturnUrl($path) {
        return $this->_storeManager->getStore()->getBaseUrl().$path;
        //return $this->getUrl($path);
    }
    
    public function getLanguage() {
        $language = $this->getMainConfigData('language');
        if ($language == \Payfort\Fort\Model\Config\Source\Languageoptions::STORE) {
            $language = $this->_localeResolver->getLocale();
        }
        if(substr($language, 0, 2) == 'ar') {
            $language = 'ar';
        }
        else{
            $language = 'en';
        }
        return $language;
    }
    
    /**
     * Restores quote
     *
     * @return bool
     */
    public function restoreQuote()
    {
        return $this->session->restoreQuote();
    }
    
    /**
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     * @return bool True if order cancelled, false otherwise
     */
    public function cancelCurrentOrder($comment)
    {
        $order = $this->session->getLastRealOrder();
        if(!empty($comment)) {
            $comment = 'Payfort_Fort :: ' . $comment;
        }
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }
    
    /**
     * Cancel order with specified comment message
     *
     * @return Mixed
     */
    public function cancelOrder($order, $comment)
    {
        $gotoSection = false;
        if(!empty($comment)) {
            $comment = 'Payfort_Fort :: ' . $comment;
        }
        if ($order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            /*if ($this->restoreQuote()) {
                //Redirect to payment step
                $gotoSection = 'paymentMethod';
            }*/
            $gotoSection = true;
        }
        return $gotoSection;
    }
    
    public function orderFailed($order) {
        if ($order->getState() != $this->getMainConfigData('order_status_on_fail')) {
            $order->setStatus($this->getMainConfigData('order_status_on_fail'));
            $order->setState($this->getMainConfigData('order_status_on_fail'));
            $order->save();
            $customerNotified = $this->sendOrderEmail($order);
            $order->addStatusToHistory( $this->getMainConfigData('order_status_on_fail') , 'Payfort_Fort :: payment has failed.', $customerNotified );
            $order->save();
            return true;
        }
        return false;
    }
    
    public function processOrder($order) {
     
        if ($order->getState() != $order::STATE_PROCESSING) {
            $order->setStatus($order::STATE_PROCESSING);
            $order->setState($order::STATE_PROCESSING);
            //$order->setExtOrderId($orderNumber);
            $order->save();
            $customerNotified = $this->sendOrderEmail($order);
            $order->addStatusToHistory( $order::STATE_PROCESSING , 'Payfort_Fort :: Order has been paid.', $customerNotified );
            $order->save();
            return true;
        }
        return false;
    }
    
    public function sendOrderEmail($order) {
        $result = true;
        try{
            if($order->getState() != $order::STATE_PROCESSING) {
                $orderCommentSender = $this->_objectManager
                    ->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
                $orderCommentSender->send($order, true, '');
            }
            else{
                $this->orderManagement->notify($order->getEntityId());
            }
        } catch (\Exception $e) {
            $result = false;
            $this->_logger->critical($e);
        }
        
        return $result;
    }
    
    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }
    
    public function validateResponse($responseData)
    {
        $debugMsg = "Response Parameters \n".print_r($responseData, 1);
        $this->log($debugMsg);
        if(empty($responseData)) {
            $this->log('Invalid Response Parameters');
            return \Payfort\Fort\Model\Payment::PAYMENT_STATUS_FAILED;
        }
        
        $responseSignature = $responseData['signature'];
        $responseGatewayParams = $responseData;
        unset($responseGatewayParams['signature']);
        $calculatedSignature = $this->calculateSignature($responseGatewayParams, 'response'); 
        if($responseSignature != $calculatedSignature) {
            $this->log(sprintf('Invalid Signature. Calculated Signature: %1s, Response Signature: %2s', $responseSignature, $calculatedSignature));
            return \Payfort\Fort\Model\Payment::PAYMENT_STATUS_FAILED;
        }
        $response_code = $responseData['response_code'];
        $response_msg  = $responseData['response_message'];
        if (substr($response_code, 2) != '000') {
            if($response_code == \Payfort\Fort\Model\Payment::PAYMENT_STATUS_CANCELED) {
                $this->log(sprintf('User has cancle the payment, Response Code (%1s), Response Message (%2s)', $response_code, $response_msg));
                return \Payfort\Fort\Model\Payment::PAYMENT_STATUS_CANCELED;
            }
            elseif($response_code == \Payfort\Fort\Model\Payment::PAYMENT_STATUS_3DS_CHECK) {
                return \Payfort\Fort\Model\Payment::PAYMENT_STATUS_3DS_CHECK;
            }
            else {
                $this->log(sprintf('Gateway error: Response Code (%1s), Response Message (%2s)', $response_code, $response_msg));
                return \Payfort\Fort\Model\Payment::PAYMENT_STATUS_FAILED;
            }
            
        }
        return \Payfort\Fort\Model\Payment::PAYMENT_STATUS_SUCCESS;
    }
    
    /**
     * Log the error on the disk
     */
    public function log($messages, $forceLog = false) {
        $debugMode = $this->getMainConfigData('debug');
        if(!$debugMode && !$forceLog) {
            return;
        }
        $debugMsg = "=============== Payfort_Fort Module =============== \n".$messages."\n";
        $this->_logger->debug($debugMsg);
    }
}
