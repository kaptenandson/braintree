<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Braintree\Persistence;

use Spryker\Zed\Kernel\Persistence\QueryContainer\QueryContainerInterface;

interface BraintreeQueryContainerInterface extends QueryContainerInterface
{
    /**
     * @api
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeQuery
     */
    public function queryPayments();

    /**
     * @api
     *
     * @param int $idPayment
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeQuery
     */
    public function queryPaymentById($idPayment);

    /**
     * @api
     *
     * @param int $idSalesOrder
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeQuery
     */
    public function queryPaymentBySalesOrderId($idSalesOrder);

    /**
     * @api
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeTransactionStatusLogQuery
     */
    public function queryTransactionStatusLog();

    /**
     * @api
     *
     * @param int $idPayment
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeTransactionStatusLogQuery
     */
    public function queryTransactionStatusLogByPaymentId($idPayment);

    /**
     * @api
     *
     * @param int $idPayment
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeTransactionStatusLogQuery
     */
    public function queryTransactionStatusLogByPaymentIdLatestFirst($idPayment);

    /**
     * @api
     *
     * @param int $idSalesOrder
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeTransactionStatusLogQuery
     */
    public function queryTransactionStatusLogBySalesOrderId($idSalesOrder);

    /**
     * @api
     *
     * @param int $idSalesOrder
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeTransactionStatusLogQuery
     */
    public function queryTransactionStatusLogBySalesOrderIdLatestFirst($idSalesOrder);

    /**
     * @api
     *
     * @param int $idSalesOrder
     * @param string $transactionCode
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeTransactionStatusLogQuery
     */
    public function queryTransactionStatusLogBySalesOrderIdAndTransactionCodeLatestFirst($idSalesOrder, $transactionCode);

    /**
     * @api
     *
     * @param int $idPayment
     *
     * @return \Orm\Zed\Braintree\Persistence\SpyPaymentBraintreeTransactionRequestLogQuery
     */
    public function queryTransactionRequestLogByPaymentId($idPayment);
}
