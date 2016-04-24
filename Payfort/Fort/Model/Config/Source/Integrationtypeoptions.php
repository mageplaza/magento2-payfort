<?php
/**
 * Payment Integration Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 * @author      Deya Zalloum (dzalloum@payfort.com)
 * @copyright   Payfort (http://www.payfort.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Payfort\Fort\Model\Config\Source;

class Integrationtypeoptions implements \Magento\Framework\Option\ArrayInterface
{
    const REDIRECTION  = "redirection";
    const MERCHANT_PAGE = "merchantPage";
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::REDIRECTION,
                'label' => __('Redirection'),
            ],
            [
                'value' => self::MERCHANT_PAGE,
                'label' => __('Merchant Page')
            ]
        ];
    }
}
