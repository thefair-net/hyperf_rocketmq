<?php

namespace TheFairLib\RocketMQ;

use TheFairLib\RocketMQ\Exception\InvalidArgumentException;
use TheFairLib\RocketMQ\Http\HttpClient;

class MQClient
{
    private $client;

    /**
     *
     * @param $endPoint
     * @param $accessId
     * @param $accessKey
     * @param null $securityToken
     * @param Config|null $config config: necessary configs
     */
    public function __construct(
        $endPoint,
        $accessId,
        $accessKey,
        $securityToken = null,
        Config $config = null
    ) {
        if (empty($endPoint)) {
            throw new InvalidArgumentException(400, "Invalid endpoint");
        }
        if (empty($accessId)) {
            throw new InvalidArgumentException(400, "Invalid accessId");
        }
        if (empty($accessKey)) {
            throw new InvalidArgumentException(400, "Invalid accessKey");
        }
        $this->client = new HttpClient(
            $endPoint,
            $accessId,
            $accessKey,
            $securityToken,
            $config
        );
    }


    /**
     * Returns a Producer reference for publish message to topic
     *
     * @param string $instanceId : instance id
     * @param string $topicName :  the topic name
     *
     * @return MQProducer: the Producer instance
     */
    public function getProducer(string $instanceId, string $topicName): MQProducer
    {
        if ($topicName == null || $topicName == "") {
            throw new InvalidArgumentException(400, "TopicName is null or empty");
        }
        return new MQProducer($this->client, $instanceId, $topicName);
    }

    /**
     * Returns a Transaction Producer reference for publish message to topic
     *
     * @param string $instanceId : instance id
     * @param string $topicName :  the topic name
     * @param string $groupId :  the group id
     *
     * @return MQTransProducer: the Transaction Producer instance
     */
    public function getTransProducer(string $instanceId, string $topicName, string $groupId): MQTransProducer
    {
        if ($topicName == null || $topicName == "") {
            throw new InvalidArgumentException(400, "TopicName is null or empty");
        }
        return new MQTransProducer($this->client, $instanceId, $topicName, $groupId);
    }

    /**
     * Returns a Consumer reference for consume and ack message to topic
     *
     * @param string $instanceId : instance id
     * @param string $topicName :  the topic name
     * @param string $consumer : the consumer name / ons cid
     * @param string|null $messageTag : filter tag for consumer. If not empty, only consume the message which's messageTag is equal to it.
     *
     * @return MQConsumer: the Consumer instance
     */
    public function getConsumer(string $instanceId, string $topicName, string $consumer, string $messageTag = null): MQConsumer
    {
        if ($topicName == null || $topicName == "") {
            throw new InvalidArgumentException(400, "TopicName is null or empty");
        }
        if ($consumer == null || $consumer == "") {
            throw new InvalidArgumentException(400, "Consumer is null or empty");
        }
        return new MQConsumer($this->client, $instanceId, $topicName, $consumer, $messageTag);
    }
}
