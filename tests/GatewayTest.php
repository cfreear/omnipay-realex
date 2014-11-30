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
}