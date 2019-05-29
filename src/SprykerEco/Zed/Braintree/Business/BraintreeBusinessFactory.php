<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Braintree\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerEco\Zed\Braintree\BraintreeDependencyProvider;
use SprykerEco\Zed\Braintree\Business\Hook\PostSaveHook;
use SprykerEco\Zed\Braintree\Business\Hook\PostSaveHookInterface;
use SprykerEco\Zed\Braintree\Business\Log\TransactionStatusLog;
use SprykerEco\Zed\Braintree\Business\Log\TransactionStatusLogInterface;
use SprykerEco\Zed\Braintree\Business\Order\Saver;
use SprykerEco\Zed\Braintree\Business\Order\SaverInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Filter\PaypalExpressPaymentMethodFilter;
use SprykerEco\Zed\Braintree\Business\Payment\Filter\PaypalExpressPaymentMethodFilterInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\AuthorizeTransaction;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\CaptureItemsTransaction;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\CaptureOrderTransaction;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\AuthorizeTransactionHandler;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\AuthorizeTransactionHandlerInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\CaptureItemsTransactionHandler;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\CaptureItemsTransactionHandlerInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\CaptureOrderTransactionHandler;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\CaptureOrderTransactionHandlerInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\PreCheckTransactionHandler;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\PreCheckTransactionHandlerInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\RefundTransactionHandler;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\RefundTransactionHandlerInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\RevertTransactionHandler;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\RevertTransactionHandlerInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\MetaVisitor\PaymentTransactionMetaVisitor;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\MetaVisitor\TransactionMetaVisitorComposite;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\MetaVisitor\TransactionMetaVisitorInterface;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\PreCheckTransaction;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\RefundTransaction;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\RevertTransaction;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\TransactionInterface;
use SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToMoneyFacadeInterface;
use SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToRefundFacadeInterface;
use SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToSalesFacadeInterface;

/**
 * @method \SprykerEco\Zed\Braintree\Persistence\BraintreeQueryContainerInterface getQueryContainer()
 * @method \SprykerEco\Zed\Braintree\BraintreeConfig getConfig()
 * @method \SprykerEco\Zed\Braintree\Persistence\BraintreeRepositoryInterface getRepository()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @method \SprykerEco\Zed\Braintree\Persistence\BraintreeEntityManagerInterface getEntityManager()
 */
class BraintreeBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\AuthorizeTransactionHandlerInterface
     */
    public function createAuthorizeTransactionHandler(): AuthorizeTransactionHandlerInterface
    {
        return new AuthorizeTransactionHandler(
            $this->createAuthorizeTransaction(),
            $this->createDefaultTransactionMetaVisitor()
        );
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\CaptureItemsTransactionHandlerInterface
     */
    public function createCaptureItemsTransactionHandler(): CaptureItemsTransactionHandlerInterface
    {
        return new CaptureItemsTransactionHandler(
            $this->createCaptureItemsTransaction(),
            $this->createDefaultTransactionMetaVisitor()
        );
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\CaptureOrderTransactionHandlerInterface
     */
    public function createCaptureOrderTransactionHandler(): CaptureOrderTransactionHandlerInterface
    {
        return new CaptureOrderTransactionHandler(
            $this->createCaptureOrderTransaction(),
            $this->createDefaultTransactionMetaVisitor()
        );
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\PreCheckTransactionHandlerInterface
     */
    public function createPreCheckTransactionHandler(): PreCheckTransactionHandlerInterface
    {
        return new PreCheckTransactionHandler(
            $this->createPreCheckTransaction(),
            $this->createDefaultTransactionMetaVisitor()
        );
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\RefundTransactionHandlerInterface
     */
    public function createRefundTransactionHandler(): RefundTransactionHandlerInterface
    {
        return new RefundTransactionHandler(
            $this->createRefundTransaction(),
            $this->createDefaultTransactionMetaVisitor(),
            $this->getRefundFacade()
        );
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\Handler\RevertTransactionHandlerInterface
     */
    public function createRevertTransactionHandler(): RevertTransactionHandlerInterface
    {
        return new RevertTransactionHandler(
            $this->createRevertTransaction(),
            $this->createDefaultTransactionMetaVisitor()
        );
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Order\SaverInterface
     */
    public function createOrderSaver(): SaverInterface
    {
        return new Saver();
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Log\TransactionStatusLogInterface
     */
    public function createTransactionStatusLog(): TransactionStatusLogInterface
    {
        return new TransactionStatusLog($this->getRepository());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Hook\PostSaveHookInterface
     */
    public function createPostSaveHook(): PostSaveHookInterface
    {
        return new PostSaveHook($this->getRepository());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\TransactionInterface
     */
    public function createAuthorizeTransaction(): TransactionInterface
    {
        return new AuthorizeTransaction($this->getConfig());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\MetaVisitor\TransactionMetaVisitorInterface
     */
    public function createDefaultTransactionMetaVisitor(): TransactionMetaVisitorInterface
    {
        $transactionMetaVisitorComposite = $this->createTransactionMetaVisitorComposite();
        $transactionMetaVisitorComposite->addVisitor($this->createPaymentTransactionMetaVisitor());

        return $transactionMetaVisitorComposite;
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\MetaVisitor\TransactionMetaVisitorInterface
     */
    public function createTransactionMetaVisitorComposite(): TransactionMetaVisitorInterface
    {
        return new TransactionMetaVisitorComposite();
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\MetaVisitor\TransactionMetaVisitorInterface
     */
    public function createPaymentTransactionMetaVisitor(): TransactionMetaVisitorInterface
    {
        return new PaymentTransactionMetaVisitor($this->getRepository());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\TransactionInterface
     */
    public function createCaptureOrderTransaction(): TransactionInterface
    {
        return new CaptureOrderTransaction($this->getConfig());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\TransactionInterface
     */
    public function createCaptureItemsTransaction(): TransactionInterface
    {
        return new CaptureItemsTransaction(
            $this->getConfig(),
            $this->getMoneyFacade(),
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getSalesFacade()
        );
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\TransactionInterface
     */
    public function createPreCheckTransaction(): TransactionInterface
    {
        return new PreCheckTransaction($this->getConfig(), $this->getMoneyFacade());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToMoneyFacadeInterface
     */
    public function getMoneyFacade(): BraintreeToMoneyFacadeInterface
    {
        return $this->getProvidedDependency(BraintreeDependencyProvider::FACADE_MONEY);
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\TransactionInterface
     */
    public function createRefundTransaction(): TransactionInterface
    {
        return new RefundTransaction($this->getConfig(), $this->getMoneyFacade());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Transaction\TransactionInterface
     */
    public function createRevertTransaction(): TransactionInterface
    {
        return new RevertTransaction($this->getConfig());
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToRefundFacadeInterface
     */
    public function getRefundFacade(): BraintreeToRefundFacadeInterface
    {
        return $this->getProvidedDependency(BraintreeDependencyProvider::FACADE_REFUND);
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Business\Payment\Filter\PaypalExpressPaymentMethodFilterInterface
     */
    public function createPaypalExpressCheckoutPaymentMethod(): PaypalExpressPaymentMethodFilterInterface
    {
        return new PaypalExpressPaymentMethodFilter();
    }

    /**
     * @return \SprykerEco\Zed\Braintree\Dependency\Facade\BraintreeToSalesFacadeInterface
     */
    public function getSalesFacade(): BraintreeToSalesFacadeInterface
    {
        return $this->getProvidedDependency(BraintreeDependencyProvider::FACADE_SALES);
    }
}
