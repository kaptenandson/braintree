<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEcoTest\Zed\Braintree\Business;

use Braintree\Exception\NotFound;
use Braintree\Transaction;
use Braintree\Transaction\StatusDetails;
use DateTime;
use SprykerEco\Zed\Braintree\BraintreeConfig;
use SprykerEco\Zed\Braintree\Business\Payment\Transaction\AuthorizeTransaction;

/**
 * Auto-generated group annotations
 * @group SprykerEcoTest
 * @group Zed
 * @group Braintree
 * @group Business
 * @group Facade
 * @group BraintreeFacadeAuthorizeTest
 * Add your own group annotations below this line
 */
class BraintreeFacadeAuthorizeTest extends AbstractFacadeTest
{
    /**
     * @return void
     */
    public function testAuthorizePaymentWithSuccessResponse()
    {
        $factoryMock = $this->getFactoryMock(['createAuthorizeTransaction']);
        $factoryMock->expects($this->once())->method('createAuthorizeTransaction')->willReturn(
            $this->getAuthorizeTransactionMock()
        );
        $braintreeFacade = $this->getBraintreeFacade($factoryMock);

        $transactionMetaTransfer = $this->getTransactionMetaTransfer();
        $response = $braintreeFacade->authorizePayment($transactionMetaTransfer);

        $this->assertTrue($response->getIsSuccess());
    }

    /**
     * @return void
     */
    public function testAuthorizePaymentWithErrorResponse()
    {
        $factoryMock = $this->getFactoryMock(['createAuthorizeTransaction']);
        $factoryMock->expects($this->once())->method('createAuthorizeTransaction')->willReturn(
            $this->getAuthorizeTransactionMock(true)
        );
        $braintreeFacade = $this->getBraintreeFacade($factoryMock);

        $transactionMetaTransfer = $this->getTransactionMetaTransfer();
        $response = $braintreeFacade->authorizePayment($transactionMetaTransfer);

        $this->assertFalse($response->getIsSuccess());
    }

    /**
     * @param bool $throwsException
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAuthorizeTransactionMock($throwsException = false)
    {
        $authorizeTransactionMock = $this
            ->getMockBuilder(AuthorizeTransaction::class)
            ->setMethods(['findTransaction', 'initializeBraintree'])
            ->setConstructorArgs(
                [new BraintreeConfig()]
            )
            ->getMock();

        if ($throwsException) {
            $authorizeTransactionMock->method('findTransaction')->willThrowException(new NotFound());
        } else {
            $transaction = $this->getSuccessfulTransaction();
            $authorizeTransactionMock->expects($this->once())
                ->method('findTransaction')
                ->willReturn($transaction);
        }

        return $authorizeTransactionMock;
    }

    /**
     * @return \Braintree\Transaction
     */
    protected function getSuccessfulTransaction()
    {
        $orderTransfer = $this->createOrderTransfer();
        return Transaction::factory([
            'id' => 123,
            'processorResponseCode' => '1000',
            'processorResponseText' => 'Approved',
            'createdAt' => new DateTime(),
            'status' => 'authorized',
            'type' => 'sale',
            'amount' => $orderTransfer->getTotals()->getGrandTotal() / 100,
            'merchantAccountId' => 'abc',
            'statusHistory' => new StatusDetails([
                'timestamp' => new DateTime(),
                'status' => 'authorized',
            ]),
        ]);
    }
}
