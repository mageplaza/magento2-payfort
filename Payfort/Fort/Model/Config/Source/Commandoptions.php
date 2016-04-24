<?php
/**
 * Payment Command Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 * @author      Deya Zalloum (dzalloum@payfort.com)
 * @copyright   Payfort (http://www.payfort.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Payfort\Fort\Model\Config\Source;

class Commandoptions implements \Magento\Framework\Option\ArrayInterface
{
    const AUTHORIZATION = 'AUTHORIZATION';
    const PURCHASE      = 'PURCHASE';
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AUTHORIZATION,
                'label' => __('AUTHORIZATION')
            ],
            [
                'value' => self::PURCHASE,
                'label' => __('PURCHASE')
            ]
        ];
    }
}
