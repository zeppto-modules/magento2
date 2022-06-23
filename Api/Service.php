<?php
/**
 * Copyright © 2020 Zeppto SAS. All rights reserved.
 * License: OSL 3.0 https://opensource.org/licenses/OSL-3.0
*/

namespace Zeppto\Magento2\Api;
use Zeppto\Magento2\Api\ServiceInterface;
use \Magento\Framework\Exception\NoSuchEntityException;

class Service implements ServiceInterface
{

    /**
     * @param \Magento\Quote\Model\QuoteManagement                $quoteManagement,
     * @param \Magento\Quote\Model\Quote\Address\Rate             $shippingRate
     * @param \Psr\Log\LoggerInterface                            $logger
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Api\OrderRepositoryInterface         $orderRepositoryInterface
     * @param \Magento\Framework\DB\TransactionFactory            $transactionFactory
     * @param \Magento\Framework\Event\ManagerInterface           $eventManager
     * @param \Magento\Framework\UrlInterface                     $urlBuilder
     * @param \Magento\Customer\Model\Session                     $customerSession
     * @param \Magento\Checkout\Model\Cart                        $cart
     * @param \Magento\Checkout\Model\Session                     $checkoutSession
     * @param \Magento\Sales\Model\Service\InvoiceService         $invoiceService
     * @param \Magento\Store\Model\StoreManagerInterface          $storeManager
     * @param \Magento\Customer\Model\CustomerFactory             $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     */
    public function __construct(
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->shippingRate = $shippingRate;
        $this->logger = $logger;
        $this->orderSender = $orderSender;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->transactionFactory = $transactionFactory;
        $this->eventManager = $eventManager;
        $this->urlBuilder = $urlBuilder;
        $this->customerSession = $customerSession;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->invoiceService = $invoiceService;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
    }

    private static function getNames($name)
    {
        $names = explode(" ", $name, 2);
        if(count($names) == 1) {
            return [$name, ""];
        } else {
            return $names;
        }
    }

    public function generateInvoice($orderId, $paymentIntentId)
    {
        try {
            $order = $this->orderRepositoryInterface->get($orderId);
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setTransactionId($paymentIntentId);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register()->pay();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $message = 'Automatically INVOICED ' . $paymentIntentId;
            $order->addStatusHistoryComment($message, false);
            $transactionSave = $this->transactionFactory
                ->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
            return $invoice;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    public function setQuoteAddresses($shippingAddress, $email, $quote, $customer)
    {
        $name = $shippingAddress['name'];
        $phoneNumber = $shippingAddress['phoneNumber'];
        $locality = $shippingAddress['locality'];
        $administrativeArea = $shippingAddress['administrativeArea'];
        $countryCode = $shippingAddress['countryCode'];
        $postalCode = $shippingAddress['postalCode'];
        $address1=$shippingAddress['address1'];
        $address2=$shippingAddress['address2'];
        $address3=$shippingAddress['address3'];

        $shippingAddress = $quote->getShippingAddress();
        $street = trim($address1);
        if(strlen($address2) > 0) {
            $street .= '\n'.$address2;
        }
        if(strlen($address3) > 0) {
            $street .= '\n'.$address3;
        }
        $shippingAddress->setStreet($street);
        $shippingAddress->setEmail($email);
        $names = $this->getNames($name);
        $shippingAddress->setFirstname($names[0]);
        $shippingAddress->setLastname($names[1]);
        $shippingAddress->setPrefix($customer->getPrefix());
        $shippingAddress->setTelephone($phoneNumber);
        $shippingAddress->setCity($locality);
        $shippingAddress->setRegionCode($administrativeArea);
        $shippingAddress->setCountryId($countryCode);
        $shippingAddress->setPostcode($postalCode);
        $shippingAddress->unsAddressId()->unsAddressType();
        $shippingAddress->setSameAsBilling(1);
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->addData($shippingAddress->getData());
        $quote->getBillingAddress()->addData($billingAddress->getData());
        $quote->getShippingAddress()->addData($shippingAddress->getData());
    }

    private static function rand_string($length) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }

    /**
     * Place Order
     *
     * @param mixed  $shippingAddress
     * @param string $paymentIntentId
     * @param string $shippingOptionId
     * @param string $paymentMethod
     * @param string $email
     * @param string $shallCreate
     * @param string $sid
     * @param string $quoteId
     * @param string $prefix
     *
     * @return string
     */
    public function orderPostMethod($shippingAddress,$paymentIntentId,$shippingOptionId, $paymentMethod, $email, $shallCreate, $sid, $quoteId, $prefix)
    {
        $redirectUrl = $this->urlBuilder->getUrl('checkout/onepage/failure', ['_secure' => true]);
        $shouldUnlog = false;
        try{
            $exist = false;
            $customerId = false;
            $isLoggedIn = $this->customerSession->isLoggedIn();
            $isGuest = false;
            if (!$isLoggedIn) {
                $websiteID = $this->storeManager->getWebsite()->getWebsiteId();
                try {
                    $customer = $this->customerFactory->create()->setWebsiteId($websiteID)->loadByEmail($email);
                    if(!$customer->getEntityId()){
                        throw NoSuchEntityException::singleField('id', $email);
                    }
                    $customerId = true;
                    $exist = true;
                    $shouldUnlog = true;
                } catch (NoSuchEntityException $e) {
                    $this->logger->debug($e->getMessage());
                    if($shallCreate) {
                        $customer = $this->customerFactory->create();
                        $customer->setWebsiteId($websiteID);
                        $names = self::getNames($shippingAddress['name']);
                        $customer->setEmail($email)
                                ->setPassword($this->rand_string(12))
                                ->setFirstname($names[0])
                                ->setLastname($names[1])
                                ->setPrefix($prefix)
                                ->save();
                        $this->customerSession->setCustomerAsLoggedIn($customer);
                        $customerId = true;
                    } else {
                        $isGuest = true;
                    }
                }
            } else {
                $customer = $this->customerSession->getCustomer();
                $exist = true;
                $customerId = true;
            }

            if($shouldUnlog) {
                $this->customerSession->setCustomerAsLoggedIn($customer);
            }

            $quote = $this->quoteRepository->get($quoteId);

            if($isLoggedIn){
                $quote
                ->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
            }else{
                if($email){
                    $quote->setCustomerEmail($email);
                }
                if($isGuest){
                    $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST)
                    ->setCustomerId(null)
                    ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                }else{
                    $datacustomer= $this->customerRepository->getById($customer->getEntityId());
                    $quote
                    ->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
    
                    $quote->assignCustomer($datacustomer);
                    $quote->setCustomerIsGuest(0); // 
                }
            }

            $quote->reserveOrderId()->save();
            if($shippingAddress) {
              $this->setQuoteAddresses($shippingAddress, $email, $quote, $customer);
            }
            if($shippingOptionId) {
              $quote->getShippingAddress()->setShippingMethod($shippingOptionId);
              $this->shippingRate
                  ->setCode($shippingOptionId)
                  ->getPrice();
              $quote->getShippingAddress()->addShippingRate($this->shippingRate);
              $quote->getShippingAddress()->setCollectShippingRates(true);
            }
            $quote->getPayment()->importData(['method' => 'zeppto_magento2']);
            $quote->setPaymentMethod('zeppto_magento2');
            $quote->getPayment()->setTransactionId($paymentIntentId);
            $quote->collectTotals();
            $quote->reserveOrderId();
            $quote->save();
            $quoteTotal = $quote->getGrandTotal();
            $quoteCurrency = $quote->getQuoteCurrencyCode();
            $data = array(
                'intent_id' => $paymentIntentId,
                'amount' => $quoteTotal * 100,
                'currency' => $quoteCurrency,
                'cart_id' => $quote->getReservedOrderId(),
                'paymentMethod' => $paymentMethod,
                'sid' => $sid
            );
            $client = new \Zend\Http\Client();
            $client->setUri('https://safeconnecty.com/capture_payment');
            $client->setOptions(array(
                'maxredirects' => 0,
                'timeout'      => 30
            ));
            $httpHeaders = new \Zend\Http\Headers();
            $httpHeaders->addHeaders([
                'Content-Type' => 'application/json',
                'Origin' => $this->storeManager->getStore()->getBaseUrl()
            ]);
            $client->setHeaders($httpHeaders);
            $client->setRawBody(json_encode($data));
            $client->setEncType('application/json');
            $client->setMethod('POST');
            $response = $client->send();
            $json = json_decode($response->getBody(), true);
            $status = $json['status'];
            if($status != 'ok') {
                if($shouldUnlog) {
                    $this->customerSession->setCustomerId(null);
                }    
                return json_encode(array(
                    'url' => $redirectUrl,
                    'reason' => "paymentrefused",
                    'shall_tell' => (!$this->customerSession->isLoggedIn() && $customerId),
                    'is_new' => (!$exist)
                ));
            }

            $order = $this->quoteManagement->submit($quote);

            if ($order) {
                $order->getPayment()->setAdditionalInformation(
                    'paymentIntentId',
                    $paymentIntentId
                );
                $this->generateInvoice($order->getId(), $paymentIntentId);

                $this->eventManager->dispatch(
                    'checkout_type_onepage_save_order_after',
                    ['order' => $order, 'quote' => $quote]
                );

                if ($order->getCanSendNewEmailFlag()) {
                    try {
                        $this->orderSender->send($order);
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                    }
                }

                $this->checkoutSession->replaceQuote($quote);
                $this->checkoutSession
                    ->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

                $this->eventManager->dispatch(
                    'checkout_submit_all_after',
                    [
                        'order' => $order,
                        'quote' => $quote
                    ]
                );
            }

            $response = [
                'url' => $this->urlBuilder->getUrl('checkout/onepage/success', ['_secure' => true]),
                'quote' => $quote->getId(),
                'order' => $order->getId()
            ];

        }catch(\Exception $e) {
            $this->logger->debug($e->getMessage());
            $response=[
                'error' => $e->getMessage(),
                'url' => $redirectUrl
            ];
        }
        if($shouldUnlog) {
            $this->customerSession->setCustomerId(null);
        }

        return json_encode($response);
    }

    /**
     * Get CartID
     *
     * @param string $maskedQuoteId
     *
     * @return string
     */
    public function cartPostMethod($maskedQuoteId)
    {
        try{
            $quote = $this->cart->getQuote();
            if (!$quote->getId()){
                if($maskedQuoteId != "0") {
                    $quoteIdMask = $this->quoteIdMaskFactory->create()->load($maskedQuoteId, 'masked_id');
                    $quoteId = $quoteIdMask->getQuoteId();
                    $quote = $this->quoteRepository->get($quoteId);
                }
            }
            $response = [
                'cartID' => $quote->getId(),
            ];

        }catch(\Exception $e) {
            $this->logger->debug($e->getMessage());
            $response=[
                'error' => $e->getMessage()
            ];
        }

        return json_encode($response);
    }

}