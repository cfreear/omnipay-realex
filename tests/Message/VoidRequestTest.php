<?php

namespace Omnipay\Realex\Message;

use Omnipay\Tests\TestCase;

class VoidRequestTest extends TestCase
{
    /**
     * @var $request VoidRequest
     */
    private $request;

    public function setUp()
    {
        $this->request = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'merchantid'            => 'merchant123',
                'account'               => 'testaccount',
                'amount'                => '42.00',
                'currency'              => 'GBP',
                'transactionId'         => '12',
                'transactionReference'  => '70874527936401179',
                'authCode'              => '12345'
            )
        );
    }

    public function testGetData()
    {
        $data = simplexml_load_string($this->request->getData());

        $this->assertInstanceOf('SimpleXMLElement', $data);

        $this->assertSame('void', (string)$data['type']);
        $this->assertSame('merchant123', (string)$data->merchantid);
        $this->assertSame('testaccount', (string)$data->account);
        $this->assertSame('12', (string)$data->orderid);
        $this->assertSame('70874527936401179', (string)$data->pasref);
        $this->assertSame('12345', (string)$data->authcode);
        $this->assertRegExp('/[\da-f]/i', (string)$data->sha1hash);
    }

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('VoidSuccess.txt');

        /* @var $response RefundResponse */
        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('Voided Successfully', $response->getMessage());
        $this->assertSame('12', $response->getTransactionId());
        $this->assertSame('14260724231204775', $response->getTransactionReference());
        $this->assertSame('00000', $response->getAuthCode());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
        $this->assertSame('', $response->getRedirectUrl());
    }

    public function testSendFailureNonexistent()
    {
        $this->setMockHttpResponse('VoidFailureNonexistent.txt');

        /* @var $response RefundResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Original transaction not found.', $response->getMessage());
        $this->assertSame('13', $response->getTransactionId());
    }

    public function testSendFailureAlreadyVoided()
    {
        $this->setMockHttpResponse('VoidFailureAlreadyVoided.txt');

        /* @var $response RefundResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('That transaction has already been voided.', $response->getMessage());
        $this->assertSame('12', $response->getTransactionId());
    }

    public function testSendFailureSettled()
    {
        $this->setMockHttpResponse('VoidFailureSettled.txt');

        /* @var $response RefundResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Can\'t void a settled transaction.', $response->getMessage());
        $this->assertSame('12', $response->getTransactionId());
    }

}
