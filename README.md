# 使用说明

主要代码来自于阿里云官方 sdk：`https://github.com/aliyunmq/mq-http-php-sdk`

hyperf rocketmq 目前只支持 http/https

## 伪代码 demo 

```php
<?php

use Hyperf\Process\ProcessManager;
use Hyperf\Utils\Context;
use TheFairLib\RocketMQ\Exception\AckMessageException;
use TheFairLib\RocketMQ\Exception\MessageNotExistException;
use TheFairLib\RocketMQ\Model\TopicMessage;
use TheFairLib\RocketMQ\MQClient;
use TheFairLib\RocketMQ\MQProducer;
use TheFairLib\RocketMQ\MQConsumer;

/**
 * RocketMQ
 *
 * @property  string $end_point
 * @property string $access_id
 * @property string $access_key
 * @property string $instance_id
 * @property MQClient $client
 * @property Config $config
 */
class RocketMQ
{
    private $params = [
        'end_point',
        'access_id',
        'access_key',
        'instance_id',
        'client',
        'config',
    ];

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->end_point = $config->getHost();
        $this->access_id = $config->getAppId();
        $this->access_key = $config->getAppKey();
        $this->instance_id = $config->getInstanceId();
        $this->config = $config;
        $this->client = new MQClient($this->end_point, $this->access_id, $this->access_key);
    }

    public function __get($name)
    {
        if (!in_array($name, $this->params)) {
            throw new ServiceException('error param', ['name' => $name]);
        }
        return Context::get(__CLASS__ . ':' . $name);
    }

    public function __set($name, $value)
    {
        if (!in_array($name, $this->params)) {
            throw new ServiceException('error param', [$name => $value]);
        }
        return Context::set(__CLASS__ . ':' . $name, $value);
    }

    /**
     * Get the value of producer
     *
     * @param string $topic
     *
     * @return MQProducer
     */
    public function getProducer($topic)
    {
        return $this->client->getProducer($this->instance_id, $topic);
    }

    /**
     * 推入队列
     *
     * @param string $topic
     * @param BaseMessage $message
     *
     * @return TopicMessage
     */
    public function publishMessage($topic, BaseMessage $message): TopicMessage
    {
        $producer = $this->getProducer($topic);

        $publishMessage = new TopicMessage($message->toString());
        $publishMessage->setMessageKey($message->getMessageType());

        if ($message->getStartDeliverTime()) {
            $publishMessage->setStartDeliverTime($message->getStartDeliverTime());
        }

        if ($message->getMessageTag()) {
            $publishMessage->setMessageTag($message->getMessageTag());
        }

        return $producer->publishMessage($publishMessage);
    }

    /**
     * Get the value of consumer
     *
     * @param string $topic
     * @param string $groupId
     * @param string $messageTag
     *
     * @return MQConsumer
     */
    public function getConsumer($topic, $groupId, $messageTag = null)
    {
        return $this->client->getConsumer($this->instance_id, $topic, $groupId, $messageTag);
    }

    /**
     * 消费队列
     *
     * @param string $topic
     * @param string $groupId
     * @param callable $func
     * @param null $messageTag
     * @param int $numOfMessages 1~16
     *
     * @return void
     * @throws Throwable
     */
    public function consumeMessage($topic, $groupId, callable $func, $messageTag = null, $numOfMessages = 1)
    {
        $consumer = $this->getConsumer($topic, $groupId, $messageTag);

        while (ProcessManager::isRunning()) {
            try {
                // 长轮询消费消息
                // 长轮询表示如果topic没有消息则请求会在服务端挂住3s，3s内如果有消息可以消费则立即返回
                $messages = $consumer->consumeMessage(
                    $numOfMessages, // 一次最多消费3条(最多可设置为16条)
                    5 // 长轮询时间（最多可设置为30秒）
                );
            } catch (Throwable $e) {
                if ($e instanceof MessageNotExistException) {
                    // 队列为空，结束消费
                    return;
                }

                throw $e;
            }

            $receiptHandles = [];

            foreach ($messages as $message) {
                $func($message);
                $receiptHandles[] = $message->getReceiptHandle();
            }

            try {
                $consumer->ackMessage($receiptHandles);
            } catch (Throwable $e) {
                if ($e instanceof AckMessageException) {
                    // 某些消息的句柄可能超时了会导致确认不成功
                    Logger::get()->error("ack_error", ['RequestId' => $e->getRequestId()]);
                    foreach ($e->getAckMessageErrorItems() as $errorItem) {
                        Logger::get()->error('ack_error:receipt_handle', [
                            $errorItem->getReceiptHandle(), $errorItem->getErrorCode(), $errorItem->getErrorCode(),
                        ]);
                    }
                    return;
                }
                throw $e;
            }
        }
    }
}
```