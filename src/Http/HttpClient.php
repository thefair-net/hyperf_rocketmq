<?php
namespace TheFairLib\RocketMQ\Http;

use Hyperf\Guzzle\CoroutineHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use TheFairLib\RocketMQ\AsyncCallback;
use TheFairLib\RocketMQ\Config;
use TheFairLib\RocketMQ\Constants;
use TheFairLib\RocketMQ\Exception\MQException;
use TheFairLib\RocketMQ\Requests\BaseRequest;
use TheFairLib\RocketMQ\Responses\BaseResponse;
use TheFairLib\RocketMQ\Responses\MQPromise;
use TheFairLib\RocketMQ\Signature\Signature;

class HttpClient
{
    private $client;
    private $endpoint;
    private $accessId;
    private $accessKey;
    private $securityToken;
    private $requestTimeout;
    private $connectTimeout;

    private $agent;

    public function __construct(
        $endPoint,
        $accessId,
        $accessKey,
        $securityToken = null,
        Config $config = null
    ) {
        if ($config == null) {
            $config = new Config;
        }
        $this->accessId = $accessId;
        $this->accessKey = $accessKey;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $endPoint,
            'handler' => HandlerStack::create(new CoroutineHandler()),
            'defaults' => [
                'headers' => [
                    'Host' => $endPoint
                ],
                'proxy' => $config->getProxy(),
                'expect' => $config->getExpectContinue()
            ]
        ]);
        $this->requestTimeout = $config->getRequestTimeout();
        $this->connectTimeout = $config->getConnectTimeout();
        $this->securityToken = $securityToken;
        $this->endpoint = $endPoint;
        $this->agent = "mq-php-sdk/1.0.1(GuzzleHttp/" . \GuzzleHttp\Client::VERSION . " PHP/" . PHP_VERSION . ")";
    }

    private function addRequiredHeaders(BaseRequest &$request)
    {
        $body = $request->generateBody();
        $queryString = $request->generateQueryString();

        $request->setBody($body);
        $request->setQueryString($queryString);

        $request->setHeader(Constants::USER_AGENT, $this->agent);
        if ($body != null) {
            $request->setHeader(Constants::CONTENT_LENGTH, strlen($body));
        }
        $request->setHeader('Date', gmdate(Constants::GMT_DATE_FORMAT));
        if (!$request->isHeaderSet(Constants::CONTENT_TYPE)) {
            $request->setHeader(Constants::CONTENT_TYPE, 'text/xml');
        }
        $request->setHeader(Constants::VERSION_HEADER, Constants::VERSION_VALUE);

        if ($this->securityToken != null) {
            $request->setHeader(Constants::SECURITY_TOKEN, $this->securityToken);
        }

        $sign = Signature::SignRequest($this->accessKey, $request);
        $request->setHeader(
            Constants::AUTHORIZATION,
            Constants::AUTH_PREFIX . " " . $this->accessId . ":" . $sign
        );
    }

    public function sendRequestAsync(
        BaseRequest $request,
        BaseResponse &$response,
        AsyncCallback $callback = null
    ) {
        $promise = $this->sendRequestAsyncInternal($request, $response, $callback);
        return new MQPromise($promise, $response);
    }

    public function sendRequest(BaseRequest $request, BaseResponse &$response)
    {
        $promise = $this->sendRequestAsync($request, $response);
        return $promise->wait();
    }

    private function sendRequestAsyncInternal(BaseRequest &$request, BaseResponse &$response, AsyncCallback $callback = null)
    {
        $this->addRequiredHeaders($request);

        $parameters = array('exceptions' => false, 'http_errors' => false);
        $queryString = $request->getQueryString();
        $body = $request->getBody();
        if ($queryString != null) {
            $parameters['query'] = $queryString;
        }
        if ($body != null) {
            $parameters['body'] = $body;
        }

        $parameters['timeout'] = $this->requestTimeout;
        $parameters['connect_timeout'] = $this->connectTimeout;

        $request = new Request(
            strtoupper($request->getMethod()),
            $request->getResourcePath(),
            $request->getHeaders()
        );
        try {
            if ($callback != null) {
                return $this->client->sendAsync($request, $parameters)->then(
                    function ($res) use (&$response, $callback) {
                        try {
                            $response->setRequestId($res->getHeaderLine("x-mq-request-id"));
                            $callback->onSucceed($response->parseResponse($res->getStatusCode(), $res->getBody()));
                        } catch (MQException $e) {
                            $callback->onFailed($e);
                        }
                    }
                );
            } else {
                return $this->client->sendAsync($request, $parameters);
            }
        } catch (TransferException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $message = $e->getResponse()->getBody();
            }
            throw new MQException($e->getCode(), $message, $e);
        }
    }
}
