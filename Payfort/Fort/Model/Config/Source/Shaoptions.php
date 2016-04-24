<?php
/**
 * Hash Algorithm Sha Types Source Model
 *
 * @category    Payfort
 * @package     Payfort_Fort
 * @author      Deya Zalloum (dzalloum@payfort.com)
 * @copyright   Payfort (http://www.payfort.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Payfort\Fort\Model\Config\Source;

class Shaoptions implements \Magento\Framework\Option\ArrayInterface
{
    const SHA1   = 'SHA-1';
    const SHA256 = 'SHA-256';
    const SHA512 = 'SHA-512';
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SHA1,
                'label' => __('SHA-1')
            ],
            [
                'value' => self::SHA256,
                'label' => __('SHA-256')
            ],
            [
                'value' => self::SHA512,
                'label' => __('SHA-512')
            ]
        ];
    }
}
