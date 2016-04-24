<?php
/**
 * Currency Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 * @author      Deya Zalloum (dzalloum@payfort.com)
 * @copyright   Payfort (http://www.payfort.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Payfort\Fort\Model\Config\Source;

class Currencyoptions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'USD',
                'label' => __('USD'),
            ],
            [
                'value' => 'AED',
                'label' => __('AED')
            ],
            [
                'value' => 'EUR',
                'label' => __('EUR')
            ],
            [
                'value' => 'EGP',
                'label' => __('EGP')
            ],
            [
                'value' => 'SAR',
                'label' => __('SAR')
            ],
            [
                'value' => 'KWD',
                'label' => __('KWD')
            ],
            [
                'value' => 'SYP',
                'label' => __('SYP')
            ],
            [
                'value' => 'QAR',
                'label' => __('QAR')
            ],
            [
                'value' => 'no_currency',
                'label' => __('Use Default')
            ]
        ];
    }
}
