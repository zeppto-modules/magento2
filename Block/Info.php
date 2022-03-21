<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 * Copyright © 2020 Zeppto SAS. All rights reserved.
 * License: OSL 3.0 https://opensource.org/licenses/OSL-3.0
*/
namespace Zeppto\Magento2\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Zeppto\Magento2\Gateway\Response\FraudHandler;

class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case FraudHandler::FRAUD_MSG_LIST:
                return implode('; ', $value);
        }
        return parent::getValueView($field, $value);
    }
}
