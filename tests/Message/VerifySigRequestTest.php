<?php

namespace Omnipay\Realex\Message;

use Omnipay\Tests\TestCase;

class VerifySigRequestTest extends TestCase
{
    /**
     * @var $request VerifySigRequest
     */
    private $request;

    public function setUp()
    {
        $this->request = new VerifySigRequest($this->getHttpClient(), $this->getHttpRequest());
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

        $this->assertSame('3ds-verifyenrolled', (string)$data['type']);
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
        $this->assertRegExp('/[\da-f]/i', (string)$data->sha1hash);
    }

    public function testSendSuccessEnrolled()
    {
        $this->setMockHttpResponse('EnrolmentSuccessEnrolled.txt');

        /* @var $response   EnrolmentResponse */
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isEnrolled());
        $this->assertSame('Enrolled', $response->getMessage());
        $this->assertNull($response->getTransactionReference());
        $this->assertTrue($response->isRedirect());
        $this->assertRegExp(
            '/^(http|https):\/\/([-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6})\b([-a-zA-Z0-9@:%_\+.~#?&\/=]*)$/',
            $response->getRedirectUrl()
        );
        $this->assertSame('http://www.acs.com', $response->getRedirectUrl());
        $this->assertSame('POST', $response->getRedirectMethod());
        $this->assertArrayHasKey('PaReq',$response->getRedirectData());
        $this->assertArrayHasKey('TermUrl',$response->getRedirectData());
        $this->assertArrayHasKey('MD',$response->getRedirectData());
    }

    public function testSendSuccessNotEnrolled()
    {
        $this->setMockHttpResponse(array('EnrolmentSuccessNotEnrolled.txt','PurchaseSuccess.txt'));

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
}
