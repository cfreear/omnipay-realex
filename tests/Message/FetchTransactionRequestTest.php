<?php

namespace Omnipay\Realex\Message;

use Omnipay\Tests\TestCase;

class FetchTransactionRequestTest extends TestCase
{
    /**
     * @var $request FetchTransactionRequest
     */
    private $request;

    public function setUp()
    {
        $this->request = new FetchTransactionRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'merchantid'    => 'merchant123',
                'account'       => 'testaccount',
                'transactionId' => '12'
            )
        );
    }

    public function testGetData()
    {
        $data = simplexml_load_string($this->request->getData());

        $this->assertInstanceOf('SimpleXMLElement', $data);

        $this->assertSame('query', (string)$data['type']);
        $this->assertSame('merchant123', (string)$data->merchantid);
        $this->assertSame('testaccount', (string)$data->account);
        $this->assertSame('12', (string)$data->orderid);
    }

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('FetchTransactionSuccess.txt');

        /* @var $response   FetchTransactionResponse */
        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('Authorised', $response->getMessage());
        $this->assertSame('12', $response->getTransactionId());
        $this->assertSame('70874527936401179', $response->getTransactionReference());
        $this->assertSame('12345', $response->getAuthCode());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
        $this->assertSame('', $response->getRedirectUrl());
    }

    public function testSendFailure()
    {
        $this->setMockHttpResponse('FetchTransactionFailure.txt');

        /* @var $response   FetchTransactionResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Original transaction not found.', $response->getMessage());
        $this->assertSame('12', $response->getTransactionId());
    }
}
