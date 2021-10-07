<?php

namespace TheFairLib\RocketMQ;

use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\Guzzle\PoolHandler;

class Config
{
    private $proxy;  // http://username:password@192.168.16.1:10
    private $connectTimeout;
    private $requestTimeout;
    private $expectContinue;
    private $handler;

    public function __construct()
    {
        $this->proxy = null;
        $this->requestTimeout = 30; // 30 seconds
        $this->connectTimeout = 3;  // 3 seconds
        $this->expectContinue = false;
        $this->handler = HandlerStack::create(new CoroutineHandler());
    }


    public function getProxy()
    {
        return $this->proxy;
    }

    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    public function getRequestTimeout(): int
    {
        return $this->requestTimeout;
    }

    public function setRequestTimeout($requestTimeout)
    {
        $this->requestTimeout = $requestTimeout;
    }

    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getExpectContinue(): bool
    {
        return $this->expectContinue;
    }

    public function setExpectContinue($expectContinue)
    {
        $this->expectContinue = $expectContinue;
    }

    /**
     * @return HandlerStack|null|PoolHandler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param PoolHandler|HandlerStack $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }
}
