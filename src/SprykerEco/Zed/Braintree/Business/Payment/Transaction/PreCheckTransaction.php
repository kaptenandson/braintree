<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Braintree\Business\Payment\Transaction;

use Braintree\PaymentInstrumentType;
use Braintree\Transaction as BraintreeTransaction;
use Braintree_TransactionLineItem;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use SprykerEco\Shared\Braintree\BraintreeConfig as SharedBraintreeConfig;
use SprykerEco\Zed\Braintree\BraintreeConfig;
use SprykerEco\Zed\Braintree\Business\Payment\Method\ApiConstants;
use SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToMoneyFacadeInterface;

class PreCheckTransaction extends AbstractTransaction
{
    public const API_REQUEST_KEY_LINE_ITEMS = 'lineItems';

    /**
     * @var \SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToMoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @param \SprykerEco\Zed\Braintree\BraintreeConfig $brainTreeConfig
     * @param \SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToMoneyFacadeInterface $moneyFacade
     */
    public function __construct(BraintreeConfig $brainTreeConfig, BraintreeToMoneyFacadeInterface $moneyFacade)
    {
        parent::__construct($brainTreeConfig);

        $this->moneyFacade = $moneyFacade;
    }

    /**
     * @return string
     */
    protected function getTransactionType()
    {
        return ApiConstants::SALE;
    }

    /**
     * PreCheck has no transaction code defined by braintree, added for logging purposes.
     *
     * @return string
     */
    protected function getTransactionCode()
    {
        return ApiConstants::STATUS_CODE_PRE_CHECK;
    }

    /**
     * @return \Braintree\Result\Error|\Braintree\Result\Successful|\Braintree\Transaction
     */
    protected function doTransaction()
    {
        $requestData = $this->getRequestData();

        return $this->preCheck($requestData);
    }

    /**
     * @param array $requestData
     *
     * @return \Braintree\Result\Error|\Braintree\Result\Successful|\Braintree\Transaction
     */
    protected function preCheck(array $requestData)
    {
        return BraintreeTransaction::sale($requestData);
    }

    /**
     * @return array
     */
    protected function getRequestData()
    {
        return [
            'amount' => $this->getAmount(),
            'paymentMethodNonce' => $this->getNonce(),
            'options' => $this->getRequestOptions(),
            'customer' => $this->getCustomerData(),
            'billing' => $this->getCustomerAddressData($this->getBillingAddress()),
            'shipping' => $this->getCustomerAddressData($this->getShippingAddress()),
            'channel' => $this->config->getChannel(),
            static::API_REQUEST_KEY_LINE_ITEMS => $this->getItems(),
        ];
    }

    /**
     * @return array
     */
    protected function getRequestOptions()
    {
        return [
            'threeDSecure' => [
                'required' => $this->config->getIs3DSecure(),
            ],
            'storeInVault' => $this->config->getIsVaulted(),
        ];
    }

    /**
     * @return array
     */
    protected function getCustomerData()
    {
        $customerTransfer = $this->getCustomer();
        $billingAddressTransfer = $this->getBillingAddress();

        return [
            'firstName' => $customerTransfer->getFirstName(),
            'lastName' => $customerTransfer->getLastName(),
            'email' => $customerTransfer->getEmail(),
            'company' => $billingAddressTransfer->getCompany(),
            'phone' => $billingAddressTransfer->getPhone(),
        ];
    }

    /**
     * @param \Generated\Shared\Transfer\AddressTransfer $addressTransfer
     *
     * @return array
     */
    protected function getCustomerAddressData(AddressTransfer $addressTransfer)
    {
        return [
            'firstName' => $addressTransfer->getFirstName(),
            'lastName' => $addressTransfer->getLastName(),
            'company' => $addressTransfer->getCompany(),
            'streetAddress' => trim(sprintf('%s %s', $addressTransfer->getAddress1(), $addressTransfer->getAddress2())),
            'extendedAddress' => $addressTransfer->getAddress3(),
            'locality' => $addressTransfer->getCity(),
            'region' => $addressTransfer->getRegion(),
            'postalCode' => $addressTransfer->getZipCode(),
            'countryCodeAlpha2' => $addressTransfer->getIso2Code(),
        ];
    }

    /**
     * @return float
     */
    protected function getAmount()
    {
        $grandTotal = $this->getQuote()->requireTotals()->getTotals()->getGrandTotal();

        return $this->moneyFacade->convertIntegerToDecimal($grandTotal);
    }

    /**
     * @return string
     */
    protected function getNonce()
    {
        return $this->getBraintreePayment()->requireNonce()->getNonce();
    }

    /**
     * @return \Generated\Shared\Transfer\BraintreePaymentTransfer
     */
    protected function getBraintreePayment()
    {
        return $this->getPayment()->requireBraintree()->getBraintree();
    }

    /**
     * @return string
     */
    protected function getPaymentSelection()
    {
        return $this->getPayment()->requirePaymentSelection()->getPaymentSelection();
    }

    /**
     * Customer is not required for guest checkout, so no `requireCustomer()`
     *
     * @return \Generated\Shared\Transfer\CustomerTransfer
     */
    protected function getCustomer()
    {
        return $this->getQuote()->getCustomer();
    }

    /**
     * @return \Generated\Shared\Transfer\PaymentTransfer
     */
    protected function getPayment()
    {
        return $this->getQuote()->requirePayment()->getPayment();
    }

    /**
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    protected function getQuote()
    {
        return $this->transactionMetaTransfer->requireQuote()->getQuote();
    }

    /**
     * @return \Generated\Shared\Transfer\AddressTransfer
     */
    protected function getBillingAddress()
    {
        return $this->getQuote()->requireBillingAddress()->getBillingAddress();
    }

    /**
     * @return \Generated\Shared\Transfer\AddressTransfer
     */
    protected function getShippingAddress()
    {
        return $this->getQuote()->requireShippingAddress()->getShippingAddress();
    }

    /**
     * @return array
     */
    protected function getItems(): array
    {
        $requestData = [];

        $itemTransfers = $this->getQuote()->getItems();


        foreach ($itemTransfers as $itemTransfer) {
            $requestData[] = [
                'name' => $itemTransfer->getName(),
                'kind' => Braintree_TransactionLineItem::DEBIT,
                'quantity' => $itemTransfer->getQuantity(),
                'unitAmount' => $this->moneyFacade->convertIntegerToDecimal($itemTransfer->getUnitPrice()),
                'unitOfMeasure' => 'unit',
                'totalAmount' => $this->moneyFacade->convertIntegerToDecimal($itemTransfer->getSumPrice()),
                'taxAmount' => $this->moneyFacade->convertIntegerToDecimal($itemTransfer->getSumTaxAmountFullAggregation()),
                'discountAmount' => $this->moneyFacade->convertIntegerToDecimal($itemTransfer->getSumDiscountAmountAggregation()),
                'productCode' => $itemTransfer->getSku(),
                'commodityCode' => $itemTransfer->getSku(),
            ];
        }

        return $requestData;
    }

    /**
     * Prevent logging
     *
     * @return void
     */
    protected function beforeTransaction()
    {
    }

    /**
     * @param \Braintree\Result\Successful|\Braintree\Result\Error|\Braintree\Transaction $response
     *
     * @return \Generated\Shared\Transfer\BraintreeTransactionResponseTransfer
     */
    protected function afterTransaction($response)
    {
        if ($this->isTransactionSuccessful($response)) {
            $this->updatePaymentForSuccessfulResponse($response);
            $transaction = $response->transaction;
            $braintreeTransactionResponseTransfer = $this->getSuccessResponseTransfer($response);
            $braintreeTransactionResponseTransfer->setCode($transaction->processorSettlementResponseCode);
            $braintreeTransactionResponseTransfer->setCreditCardType($transaction->creditCardDetails->cardType);
            $braintreeTransactionResponseTransfer->setPaymentType($transaction->paymentInstrumentType);

            return $braintreeTransactionResponseTransfer;
        }

        $this->updatePaymentForErrorResponse($response);

        $braintreeTransactionResponseTransfer = $this->getErrorResponseTransfer($response);

        return $braintreeTransactionResponseTransfer;
    }

    /**
     * @param \Braintree\Result\Successful|\Braintree\Result\Error|\Braintree\Transaction $response
     *
     * @return bool
     */
    protected function isTransactionSuccessful($response)
    {
        return ($response->success && $this->isValidPaymentType($response));
    }

    /**
     * @param \Braintree\Result\Successful $response
     *
     * @return void
     */
    protected function updatePaymentForSuccessfulResponse($response)
    {
        $braintreePaymentTransfer = $this->getBraintreePayment();
        $braintreePaymentTransfer->setPaymentType($response->transaction->paymentInstrumentType);

        if ($braintreePaymentTransfer->getPaymentType() === PaymentInstrumentType::PAYPAL_ACCOUNT) {
            $this->setPaypalPaymentMethod($this->getPayment());
        } elseif ($braintreePaymentTransfer->getPaymentType() === PaymentInstrumentType::CREDIT_CARD) {
            $this->setCreditCardMethod($this->getPayment());
        }
    }

    /**
     * When error occurs unset nonce, so this cannot be used anymore
     *
     * @param \Braintree\Result\Error $response
     *
     * @return void
     */
    protected function updatePaymentForErrorResponse($response)
    {
        $this->getBraintreePayment()->setNonce('');
    }

    /**
     * @param \Braintree\Result\Successful $response
     *
     * @return bool
     */
    protected function isValidPaymentType($response)
    {
        $returnedType = $response->transaction->paymentInstrumentType;

        $matching = [
            SharedBraintreeConfig::PAYMENT_METHOD_PAY_PAL => PaymentInstrumentType::PAYPAL_ACCOUNT,
            SharedBraintreeConfig::PAYMENT_METHOD_CREDIT_CARD => PaymentInstrumentType::CREDIT_CARD,
            SharedBraintreeConfig::PAYMENT_METHOD_PAY_PAL_EXPRESS => PaymentInstrumentType::PAYPAL_ACCOUNT,
        ];

        return ($matching[$this->getPaymentSelection()] === $returnedType);
    }

    /**
     * @param \Generated\Shared\Transfer\PaymentTransfer $paymentTransfer
     *
     * @return \Generated\Shared\Transfer\PaymentTransfer
     */
    protected function setPaypalPaymentMethod(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $paymentTransfer->setPaymentProvider(SharedBraintreeConfig::PROVIDER_NAME);
        $paymentTransfer->setPaymentMethod(PaymentTransfer::BRAINTREE_PAY_PAL);

        if ($this->transactionMetaTransfer->getQuote()->getPayment()->getPaymentSelection() === PaymentTransfer::BRAINTREE_PAY_PAL_EXPRESS) {
            $paymentTransfer->setPaymentMethod(PaymentTransfer::BRAINTREE_PAY_PAL_EXPRESS);
            $paymentTransfer->setPaymentSelection(PaymentTransfer::BRAINTREE_PAY_PAL_EXPRESS);
        }

        return $paymentTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\PaymentTransfer $paymentTransfer
     *
     * @return \Generated\Shared\Transfer\PaymentTransfer
     */
    protected function setCreditCardMethod(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $paymentTransfer->setPaymentMethod(PaymentTransfer::BRAINTREE_CREDIT_CARD);
        $paymentTransfer->setPaymentProvider(SharedBraintreeConfig::PROVIDER_NAME);
        $paymentTransfer->setPaymentSelection(PaymentTransfer::BRAINTREE_CREDIT_CARD);

        return $paymentTransfer;
    }
}
