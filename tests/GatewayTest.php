<?php

namespace Omnipay\Realex;

use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new RemoteGateway($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testPurchase()
    {
        $request = $this->gateway->purchase(array('amount' => '10.00'));

        $this->assertInstanceOf('Omnipay\Realex\Message\AuthRequest', $request);
        $this->assertSame('10.00', $request->getAmount());
    }

    public function testPurchase3dSecure()
    {
        $this->gateway->set3dSecure(1);

        $request = $this->gateway->purchase(array('amount' => '10.00'));

        $this->assertInstanceOf('Omnipay\Realex\Message\EnrolmentRequest', $request);
        $this->assertSame('10.00', $request->getAmount());
    }

    public function testCompletePurchase()
    {
        $request = $this->gateway->completePurchase(array('amount' => '10.00'));

        $this->assertInstanceOf('Omnipay\Realex\Message\VerifySigRequest', $request);
        $this->assertSame('10.00', $request->getAmount());
    }

    public function testFetchTransaction()
    {
        $request = $this->gateway->fetchTransaction(array('transactionId' => '12345'));

        $this->assertInstanceOf('Omnipay\Realex\Message\FetchTransactionRequest', $request);
        $this->assertSame('12345', $request->getTransactionId());
    }

    public function testRefund()
    {
        $this->gateway->setRefundPassword('ABCD');

        $request = $this->gateway->refund(array('amount' => '10.00'));

        $this->assertInstanceOf('Omnipay\Realex\Message\RefundRequest', $request);
        $this->assertSame('ABCD', $this->gateway->getRefundPassword());
        $this->assertSame('10.00', $request->getAmount());
    }

    public function testVoid()
    {
        $request = $this->gateway->void(array('transactionId' => '12345'));

        $this->assertInstanceOf('Omnipay\Realex\Message\VoidRequest', $request);
        $this->assertSame('12345', $request->getTransactionId());
    }
}