<?php
namespace TheFairLib\RocketMQ\Responses;

use TheFairLib\RocketMQ\Common\XMLParser;
use TheFairLib\RocketMQ\Constants;
use TheFairLib\RocketMQ\Exception\InvalidArgumentException;
use TheFairLib\RocketMQ\Exception\MalformedXMLException;
use TheFairLib\RocketMQ\Exception\MQException;
use TheFairLib\RocketMQ\Exception\TopicNotExistException;
use TheFairLib\RocketMQ\Model\Message;
use TheFairLib\RocketMQ\Model\TopicMessage;

class PublishMessageResponse extends BaseResponse
{
    public function __construct()
    {
    }

    public function parseResponse($statusCode, $content)
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 201) {
            $this->succeed = true;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }

        $xmlReader = $this->loadXmlContent($content);
        try {
            return $this->readMessageIdAndMD5XML($xmlReader);
        } catch (\Exception $e) {
            throw new MQException($statusCode, $e->getMessage(), $e);
        } catch (\Throwable $t) {
            throw new MQException($statusCode, $t->getMessage());
        }
    }

    public function readMessageIdAndMD5XML(\XMLReader $xmlReader)
    {
        $message = Message::fromXML($xmlReader, true);
        $topicMessage = new TopicMessage(null);
        $topicMessage->setMessageId($message->getMessageId());
        $topicMessage->setMessageBodyMD5($message->getMessageBodyMD5());
        $topicMessage->setReceiptHandle($message->getReceiptHandle());

        return $topicMessage;
    }

    public function parseErrorResponse($statusCode, $content, MQException $exception = null)
    {
        $this->succeed = false;
        $xmlReader = $this->loadXmlContent($content);
        try {
            $result = XMLParser::parseNormalError($xmlReader);
            if ($result['Code'] == Constants::TOPIC_NOT_EXIST) {
                throw new TopicNotExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            if ($result['Code'] == Constants::INVALID_ARGUMENT) {
                throw new InvalidArgumentException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            if ($result['Code'] == Constants::MALFORMED_XML) {
                throw new MalformedXMLException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            throw new MQException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
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
}
