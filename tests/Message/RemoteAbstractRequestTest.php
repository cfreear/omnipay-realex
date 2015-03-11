<?php

namespace Omnipay\Realex\Message;

use Mockery;
use Omnipay\Tests\TestCase;

class RemoteAbstractRequestTest extends TestCase
{
    /**
     * @var $request RemoteAbstractRequest
     */
    protected $request;

    public function setUp()
    {
        $this->request = Mockery::mock('\Omnipay\Realex\Message\RemoteAbstractRequest')->makePartial();
        $this->request->initialize();
    }

    public function testReturnUrl()
    {
        $this->assertSame($this->request, $this->request->setReturnUrl('https://www.example.com'));
        $this->assertSame('https://www.example.com', $this->request->getReturnUrl());
    }
}
