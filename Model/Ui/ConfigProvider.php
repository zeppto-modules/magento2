<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 * Copyright © 2020 Zeppto SAS. All rights reserved.
 * License: OSL 3.0 https://opensource.org/licenses/OSL-3.0
 */
namespace Zeppto\Magento2\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Zeppto\Magento2\Gateway\Http\Client\ZepptoMagento2Client;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'zeppto_magento2';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ZepptoMagento2Client::SUCCESS => __('Success'),
                        ZepptoMagento2Client::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}
