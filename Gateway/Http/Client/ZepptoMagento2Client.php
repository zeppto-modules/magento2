<?php
/**
* Copyright © 2016 Magento. All rights reserved.
* See COPYING.txt for license details.
* Copyright © 2020 Zeppto SAS. All rights reserved.
* License: OSL 3.0 https://opensource.org/licenses/OSL-3.0
*/
namespace Zeppto\Magento2\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class ZepptoMagento2Client implements ClientInterface
{
  const SUCCESS = 1;
  const FAILURE = 0;
  const RESULT_CODE = "RESULT_CODE";
  const TRANSACTION_ID = "TXN_ID";

  /**
  * @var Logger
  */
  private $logger;

  /**
  * @param Logger $logger
  */
  public function __construct(Logger $logger)
  {
    $this->logger = $logger;
  }

  /**
  * Places request to gateway. Returns result as ENV array
  *
  * @param TransferInterface $transferObject
  * @return array
  */
  public function placeRequest(TransferInterface $transferObject)
  {
    $response = $transferObject->getBody();

    $this->logger->debug([
      'request' => $transferObject->getBody(),
      'response' => $response
    ]);

    return $response;
  }
}
