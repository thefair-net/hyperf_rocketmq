<?php
namespace TheFairLib\RocketMQ;

use TheFairLib\RocketMQ\Exception\InvalidArgumentException;
use TheFairLib\RocketMQ\Http\HttpClient;
use TheFairLib\RocketMQ\Model\TopicMessage;
use TheFairLib\RocketMQ\Requests\PublishMessageRequest;
use TheFairLib\RocketMQ\Responses\PublishMessageResponse;

class MQProducer
{
    protected $instanceId;
    protected $topicName;
    protected $client;

    public function __construct(HttpClient $client, $instanceId = null, $topicName)
    {
        if (empty($topicName)) {
            throw new InvalidArgumentException(400, "TopicName is null");
        }
        $this->instanceId = $instanceId;
        $this->client = $client;
        $this->topicName = $topicName;
    }

    public function getInstanceId()
    {
        return $this->instanceId;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function publishMessage(TopicMessage $topicMessage)
    {
        $request = new PublishMessageRequest(
            $this->instanceId,
            $this->topicName,
            $topicMessage->getMessageBody(),
            $topicMessage->getProperties(),
            $topicMessage->getMessageTag()
        );
        $response = new PublishMessageResponse();
        return $this->client->sendRequest($request, $response);
    }
}
