<?php
namespace TheFairLib\RocketMQ\Responses;

use TheFairLib\RocketMQ\Common\XMLParser;
use TheFairLib\RocketMQ\Constants;
use TheFairLib\RocketMQ\Exception\AckMessageException;
use TheFairLib\RocketMQ\Exception\InvalidArgumentException;
use TheFairLib\RocketMQ\Exception\MQException;
use TheFairLib\RocketMQ\Exception\ReceiptHandleErrorException;
use TheFairLib\RocketMQ\Exception\TopicNotExistException;
use TheFairLib\RocketMQ\Model\AckMessageErrorItem;

class AckMessageResponse extends BaseResponse
{
    public function __construct()
    {
    }

    public function parseResponse($statusCode, $content)
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 204) {
            $this->succeed = true;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }
    }

    public function parseErrorResponse($statusCode, $content, MQException $exception = null)
    {
        $this->succeed = false;
        $xmlReader = $this->loadXmlContent($content);

        try {
            while ($xmlReader->read()) {
                if ($xmlReader->nodeType == \XMLReader::ELEMENT) {
                    switch ($xmlReader->name) {
                    case Constants::ERROR:
                        $this->parseNormalErrorResponse($xmlReader);
                        break;
                    default: // case Constants::Messages
                        $this->parseAckMessageErrorResponse($xmlReader);
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            if ($exception != null) {
                throw $exception;
            } elseif ($e instanceof MQException) {
                throw $e;
            } else {
                throw new MQException($statusCode, $e->getMessage());
            }
        } catch (\Throwable $t) {
            throw new MQException($statusCode, $t->getMessage());
        }
    }

    private function parseAckMessageErrorResponse($xmlReader)
    {
        $ex = new AckMessageException($this->statusCode, "AckMessage Failed For Some ReceiptHandles");
        $ex->setRequestId($this->getRequestId());
        while ($xmlReader->read()) {
            if ($xmlReader->nodeType == \XMLReader::ELEMENT && $xmlReader->name == Constants::ERROR) {
                $ex->addAckMessageErrorItem(AckMessageErrorItem::fromXML($xmlReader));
            }
        }
        throw $ex;
    }

    private function parseNormalErrorResponse($xmlReader)
    {
        $result = XMLParser::parseNormalError($xmlReader);

        if ($result['Code'] == Constants::INVALID_ARGUMENT) {
            throw new InvalidArgumentException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        if ($result['Code'] == Constants::TOPIC_NOT_EXIST) {
            throw new TopicNotExistException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        if ($result['Code'] == Constants::RECEIPT_HANDLE_ERROR) {
            throw new ReceiptHandleErrorException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
        }

        throw new MQException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
    }
}
