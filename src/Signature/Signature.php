<?php

namespace TheFairLib\RocketMQ\Signature;

use TheFairLib\RocketMQ\Constants;
use TheFairLib\RocketMQ\Requests\BaseRequest;

class Signature
{
    public static function SignRequest($accessKey, BaseRequest $request): string
    {
        $headers = $request->getHeaders();
        $contentMd5 = "";
        if (isset($headers['Content-MD5'])) {
            $contentMd5 = $headers['Content-MD5'];
        }
        $contentType = "";
        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
        }
        $date = $headers['Date'];
        $queryString = $request->getQueryString();
        $resource = $request->getResourcePath();
        if ($queryString != null) {
            $resource .= "?" . $request->getQueryString();
        }
        if (0 !== strpos($resource, "/")) {
            $resource = "/" . $resource;
        }

        $tmpHeaders = [];
        foreach ($headers as $key => $value) {
            if (0 === strpos($key, Constants::HEADER_PREFIX)) {
                $tmpHeaders[$key] = $value;
            }
        }
        ksort($tmpHeaders);

        $headers = implode("\n", array_map(function ($v, $k) {
            return $k . ":" . $v;
        }, $tmpHeaders, array_keys($tmpHeaders)));

        $stringToSign = strtoupper($request->getMethod()) . "\n" . $contentMd5 . "\n" . $contentType . "\n" . $date . "\n" . $headers . "\n" . $resource;

        return base64_encode(hash_hmac("sha1", $stringToSign, $accessKey, true));
    }
}
