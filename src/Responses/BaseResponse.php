<?php

namespace TheFairLib\RocketMQ\Responses;

use Exception;
use TheFairLib\RocketMQ\Exception\MQException;
use XMLReader;

abstract class BaseResponse
{
    protected $succeed;
    protected $statusCode;
    // from header
    protected $requestId;

    abstract public function parseResponse($statusCode, $content);

    abstract public function parseErrorResponse($statusCode, $content, MQException $exception = null);

    public function isSucceed()
    {
        return $this->succeed;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    protected function loadXmlContent($content): XMLReader
    {
        $xmlReader = new XMLReader();
        $isXml = $xmlReader->XML($content);
        if ($isXml === false) {
            throw new MQException($this->statusCode, $content);
        }
        try {
            $i = 0;
            while ($xmlReader->read()) {
                $i++;
            }
        } catch (Exception $e) {
            throw new MQException($this->statusCode, $content);
        }
        $xmlReader->XML($content);
        return $xmlReader;
    }
}
