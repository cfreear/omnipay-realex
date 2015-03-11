<?php

namespace Omnipay\Realex\Message;

use Omnipay\Tests\TestCase;

class RefundRequestTest extends TestCase
{
    /**
     * @var $request RefundRequest
     */
    private $request;

    public function setUp()
    {
        $this->request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'merchantid'            => 'merchant123',
                'account'               => 'testaccount',
                'amount'                => '42.00',
                'currency'              => 'GBP',
                'transactionId'         => '12',
                'transactionReference'  => '70874527936401179',
                'authCode'              => '12345',
                'refundPassword'        => 'ABCD'
            )
        );
    }

    public function testGetData()
    {
        $data = simplexml_load_string($this->request->getData());

        $this->assertInstanceOf('SimpleXMLElement', $data);

        $this->assertSame('rebate', (string)$data['type']);
        $this->assertSame('merchant123', (string)$data->merchantid);
        $this->assertSame('testaccount', (string)$data->account);
        $this->assertSame('12', (string)$data->orderid);
        $this->assertSame('70874527936401179', (string)$data->pasref);
        $this->assertSame('12345', (string)$data->authcode);
        $this->assertSame('GBP', (string)$data->amount['currency']);
        $this->assertSame(4200, (int)$data->amount);
        $this->assertRegExp('/[\da-f]/i', (string)$data->sha1hash);
        $this->assertRegExp('/[\da-f]/i', (string)$data->refundhash);
    }

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('RefundSuccess.txt');

        /* @var $response RefundResponse */
        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('AUTH CODE: 123456', $response->getMessage());
        $this->assertSame('12', $response->getTransactionId());
        $this->assertSame('14260706436347024', $response->getTransactionReference());
        $this->assertSame('12345', $response->getAuthCode());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
        $this->assertSame('', $response->getRedirectUrl());
    }

    public function testSendFailureNonexistent()
    {
        $this->setMockHttpResponse('RefundFailureNonexistent.txt');

        /* @var $response RefundResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Original transaction not found.', $response->getMessage());
        $this->assertSame('_rebate_12', $response->getTransactionId());
    }

    public function testSendFailureAlreadyRefunded()
    {
        $this->setMockHttpResponse('RefundFailureAlreadyRefunded.txt');

        /* @var $response RefundResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('You may only refund up to 115% of the original amount.', $response->getMessage());
        $this->assertSame('_rebate_12', $response->getTransactionId());
    }

}
