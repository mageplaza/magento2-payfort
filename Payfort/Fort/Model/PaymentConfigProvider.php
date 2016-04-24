<?php

namespace Payfort\Fort\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class PaymentConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        \Payfort\Fort\Model\Method\Cc::CODE,
        \Payfort\Fort\Model\Method\Sadad::CODE,
        \Payfort\Fort\Model\Method\Naps::CODE
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        UrlInterface $urlBuilder
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->urlBuilder = $urlBuilder;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'payfortFort' => [],
            ],
        ];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['payfortFort'][$code]['redirectUrl'] = $this->getActionUrl($code);
                $config['payment']['payfortFort'][$code]['instructions'] = $this->methods[$code]->getInstructions();
                if($code == \Payfort\Fort\Model\Method\Cc::CODE) {
                    $config['payment']['payfortFort'][$code]['isMerchantPage'] = $this->methods[$code]->isMerchantPage();
                    $config['payment']['payfortFort'][$code]['merchantPageUrl'] = $this->urlBuilder->getUrl('payfortfort/payment/merchantPage', ['_secure' => true]);
                }
            }
        }
        return $config;
    }

    /**
     * Get frame action URL
     *
     * @param string $code
     * @return string
     */
    protected function getActionUrl($code)
    {
        $url = $this->urlBuilder->getUrl('payfortfort/payment/redirect', ['_secure' => true]);

        return $url;
    }
}
