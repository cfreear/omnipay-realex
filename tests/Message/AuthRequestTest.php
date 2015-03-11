<?php

namespace Omnipay\Realex\Message;

use Omnipay\Tests\TestCase;

class AuthRequestTest extends TestCase
{
    /**
     * @var $request AuthRequest
     */
    private $request;

    public function setUp()
    {
        $this->request = new AuthRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'merchantid'    => 'merchant123',
                'account'       => 'testaccount',
                'amount'        => '42.00',
                'currency'      => 'GBP',
                'card'          => $this->getValidCard(),
                'description'   => 'Order #12',
                'transactionId' => '12'
            )
        );
    }

    public function testGetData()
    {
        $data = simplexml_load_string($this->request->getData());

        $this->assertInstanceOf('SimpleXMLElement', $data);

        $this->assertSame('auth', (string)$data['type']);
        $this->assertSame('merchant123', (string)$data->merchantid);
        $this->assertSame('testaccount', (string)$data->account);
        $this->assertSame('12', (string)$data->orderid);
        $this->assertSame('GBP', (string)$data->amount['currency']);
        $this->assertSame(4200, (int)$data->amount);
        $this->assertRegExp('/^\d{12,19}$/', (string)$data->card->number);
        $this->assertSame('4111111111111111', (string)$data->card->number);
        $this->assertRegExp('/^[0,1]\d[0-3]\d$/', (string)$data->card->expdate);
        $this->assertSame('VISA', (string)$data->card->type);
        $this->assertSame('Example User', (string)$data->card->chname);
        $this->assertRegExp('/\d{3}/', (string)$data->card->cvn->number);
        $this->assertSame(1, (int)$data->card->cvn->presind);
        $this->assertSame(1, (int)$data->autosettle['flag']);
        $this->assertRegExp('/[\da-f]/i', (string)$data->sha1hash);
        $this->assertSame('billing', (string)$data->tssinfo->address['type']);
        $this->assertSame('US', (string)$data->tssinfo->address->country);
    }

    public function testDataWith3DSecure()
    {
        $this->request->setCavv('AAACAWQWaRKIFwQlVBZpAAAAAAA=');
        $this->request->setEci(5);
        $this->request->setXid('l2ncCuvKNtCtRY3OoC/ztHS8ZvI=');

        $data = simplexml_load_string($this->request->getData());

        $this->assertInstanceOf('SimpleXMLElement', $data);

        $this->assertSame('AAACAWQWaRKIFwQlVBZpAAAAAAA=', (string)$data->mpi->cavv);
        $this->assertSame(5, (int)$data->mpi->eci);
        $this->assertSame('l2ncCuvKNtCtRY3OoC/ztHS8ZvI=', (string)$data->mpi->xid);
    }

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        /* @var $response   AuthResponse */
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
        $this->setMockHttpResponse('PurchaseFailure.txt');

        /* @var $response   AuthResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('This transaction (12) has already been processed! If you feel this is incorrect please contact the merchant!', $response->getMessage());
        $this->assertSame('12', $response->getTransactionId());
    }

    public function testCardBrandMap()
    {
        $this->request->setCard(array(
            'number' => '5500005555555559'
        ));

        $data = simplexml_load_string($this->request->getData());

        $this->assertInstanceOf('SimpleXMLElement', $data);

        $this->assertSame('MC', (string)$data->card->type);
    }
}
