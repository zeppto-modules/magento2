<?php
/**
 * Copyright © 2020 Zeppto SAS. All rights reserved.
 * License: OSL 3.0 https://opensource.org/licenses/OSL-3.0
*/
namespace Zeppto\Magento2\Api;

/**
 * Interface ServiceInterface
 * @api
 * @since 2.0
 */
interface ServiceInterface
{
    /**
     * POST for Zeppto Magento2 api
     *
     * @param  mixed  $shippingAddress
     * @param  string $paymentIntentId
     * @param  string $shippingOptionId
     * @param  string $paymentMethod
     * @param  string $email
     * @param  string $shallCreate
     * @param  string $sid
     * @param  string $quoteId
     * @param  string $prefix
     * @return string Json Object
     * @since 2.0
     */
    public function orderPostMethod($shippingAddress,$paymentIntentId,$shippingOptionId, $paymentMethod, $email, $shallCreate, $sid ,$quoteId, $prefix);

    /**
     * POST for Zeppto Magento2 api
     *
     * @param  string $maskedQuoteId
     * @return string Json Object
     * @since 2.0
    */
    public function cartPostMethod($maskedQuoteId);

    /**
     * POST for Save order
     *
     * @param string $paymentIntentId
     * @return string Json Object
     * @since 2.0
     */
    public function savePostMethod($paymentIntentId);

    /**
     * POST for Cancel order
     *
     * @param string $orderId
     * @return string Json Object
     * @since 2.0
     */
    public function cancelPostMethod($orderId);

    /**
     * POST for Check order
     *
     * @param string $orderId
     * @return string Json Object
     * @since 2.0
     */
    public function checkPostMethod($orderId);

    /**
     * POST for Cancel order
     *
     * @return string Json Object
     * @since 2.0
     */
    public function preparePostMethod();
}