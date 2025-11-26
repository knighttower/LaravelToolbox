<?php

namespace Knighttower\Toolbox\Helpers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Http;

// https://github.com/jespanag/Laravel-Proxy-Helper/tree/master
class ProxyHelper
{

    /**
     * The original request instance.
     *
     * @var Request
     */
    private Request $originalRequest;

    /**
     * The multipart parameters for the request.
     *
     * @var array
     */
    private $multipartParams;

    /**
     * The headers for the request.
     *
     * @var array
     */
    private $headers;

    /**
     * The authorization for the request.
     *
     * @var array
     */
    private $authorization = [];

    /**
     * The custom method for the request.
     *
     * @var string
     */
    private $customMethod;

    /**
     * Preserve the query string for the request.
     *
     * @var bool
     */
    private $addQuery = false;

    /**
     * Use the default authentication for the request.
     *
     * @var bool
     */
    private $useDefaultAuth;

    /**
     * The host to send the request to.
     *
     * @var string
     */
    private $proxiedHost;

    /**
     * Include the response headers in the response.
     *
     * @var bool
     */
    private $responseHeaders = false;

    /**
     * Create a new ProxyHelper instance.
     *
     * @param Request $request
     * @param bool $useDefaultAuth
     * @return ProxyHelper
     */
    public function createProxy(Request $request, $useDefaultAuth = true)
    {
        $this->originalRequest = $request;
        $this->multipartParams = $this->getMultipartParams();
        $this->useDefaultAuth = $useDefaultAuth;
        return $this;
    }

    /**
     * Set the headers for the request.
     *
     * @param array $headers
     * @return ProxyHelper
     */
    public function withHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Set the basic auth for the request.
     *
     * @param string $user
     * @param string $secret
     * @return ProxyHelper
     */
    public function withBasicAuth($user, $secret)
    {
        $this->authorization = ['type' => 'basic', 'user' => $user, 'secret' => $secret];
        return $this;
    }

    /**
     * Set the digest auth for the request.
     *
     * @param string $user
     * @param string $secret
     * @return ProxyHelper
     */
    public function withDigestAuth($user, $secret)
    {
        $this->authorization = ['type' => 'digest', 'user' => $user, 'secret' => $secret];
        return $this;
    }

    /**
     * Set the token auth for the request.
     *
     * @param string $token
     * @return ProxyHelper
     */
    public function withToken($token)
    {
        $this->authorization = ['type' => 'token', 'token' => $token];
        return $this;
    }

    /**
     * Set the method for the request.
     *
     * @param string $method
     * @return ProxyHelper
     */
    public function withMethod($method = 'POST')
    {
        $this->customMethod = $method;
        return $this;
    }

    /**
     * Include the response headers in the response.
     *
     * @return ProxyHelper
     */
    public function withResponseHeaders()
    {
        $this->responseHeaders = true;
        return $this;
    }

    /**
     * Preserve the query string for the request.
     *
     * @param bool $preserve
     * @return ProxyHelper
     */
    public function preserveQuery($preserve = true)
    {
        $this->addQuery = $preserve;
        return $this;
    }

    /**
     * Send the request to the specified URL.
     *
     * @param string $url
     * @return \Illuminate\Http\Response
     */
    private function getResponse($url)
    {
        $info = $this->getRequestInfo();
        $http = $this->createHttp($info['type'])->connectTimeout(3)->retry(2, 500);

        $http = $this->setAuth($http);
        $http = $this->setHeaders($http);


        if ($this->addQuery && $info['query']) {
            $url = $url . '?' . http_build_query($info['query']);
        }

        try {
            $response = $this->call($http, $info['method'], $url, $this->getParams($info));
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }


        $proxyResponse = response($this->isJson($response) ? $response->json() : $response->body(), $response->status());

        if ($this->responseHeaders) {
            return $proxyResponse->withHeaders($response->headers());
        }

        return $proxyResponse;
    }

    /**
     * Use the request path for the URL.
     *
     * @return $this
     */
    public function useRequestPath()
    {
        $path = '/' . ltrim($this->originalRequest->path(), '/');
        $this->proxiedHost = $this->proxiedHost . $path;
        return $this;
    }

    /**
     * Send the request to the specified host.
     *
     * @param string $host
     * @return $this
     */
    public function toHost($host)
    {
        $this->proxiedHost = $host;
        return $this;
    }

    /**
     * Alias for the toHost method.
     *
     * @param string $url
     * @return $this
     */
    public function toUrl($url)
    {
        return $this->toHost($url);
    }

    /**
     * Send the request to the specified host and port.
     *
     * @param string $host
     * @param string $port
     * @return $this
     */
    public function toHostWithPort($host, $port)
    {
        $this->proxiedHost = $host . ':' . $port;
        return $this;
    }

    /**
     * Use the specified path for the request.
     *
     * @param string $path
     * @return $this
     */
    public function usePath($path)
    {
        $this->proxiedHost = $this->proxiedHost . '/' . ltrim($path, '/');
        return $this;
    }

    /**
     * Send the request to the specified URL.
     *
     * @return \Illuminate\Http\Response
     */
    public function send()
    {
        return $this->getResponse($this->proxiedHost);
    }

    /**
     * Get the parameters for the request.
     *
     * @param array $info
     * @return array
     */
    private function getParams($info)
    {
        if ($info['method'] === 'GET') {
            return $info['params'];
        }

        $defaultParams = $info['type'] === 'multipart' ? $this->multipartParams : $info['params'];

        if ($info['query']) {
            foreach ($info['query'] as $key => $value) {
                unset($defaultParams[array_search(['name' => $key, 'contents' => $value], $defaultParams)]);
            }
        }

        return $defaultParams;
    }

    /**
     * Set the authentication for the request.
     *
     * @param PendingRequest $request
     * @return PendingRequest
     */
    private function setAuth(PendingRequest $request)
    {
        switch ($this->authorization['type'] ?? null) {
            case 'basic':
                return $request->withBasicAuth($this->authorization['user'], $this->authorization['secret']);
            case 'digest':
                return $request->withDigestAuth($this->authorization['user'], $this->authorization['secret']);
            case 'token':
                return $request->withToken($this->authorization['token']);
            default:
                return $request;
        }
    }

    /**
     * Set the headers for the request.
     *
     * @param PendingRequest $request
     * @return PendingRequest
     */
    private function setHeaders(PendingRequest $request)
    {
        return $this->headers ? $request->withHeaders($this->headers) : $request;
    }

    /**
     * Create the HTTP client for the request.
     *
     * @param string $type
     * @return PendingRequest
     */
    private function createHttp($type)
    {
        switch ($type) {
            case 'multipart':
                return Http::asMultipart();
            case 'form':
                return Http::asForm();
            case 'json':
                return Http::asJson();
            case null:
                return new PendingRequest();
            default:
                return Http::contentType($type);
        }
    }

    /**
     * Make the HTTP call to the specified URL.
     *
     * @param PendingRequest $request
     * @param string $method
     * @param string $url
     * @param array $params
     * @return Response
     * @throws \InvalidArgumentException
     */
    private function call(PendingRequest $request, $method, $url, $params)
    {
        $method = $this->customMethod ?? $method;

        switch ($method) {
            case 'GET':
                return $request->get($url, $params);
            case 'HEAD':
                return $request->head($url, $params);
            case 'POST':
                return $request->post($url, $params);
            case 'PATCH':
                return $request->patch($url, $params);
            case 'PUT':
                return $request->put($url, $params);
            case 'DELETE':
                return $request->delete($url, $params);
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: $method");
        }
    }

    /**
     * Get the request information.
     *
     * @return array
     */
    private function getRequestInfo()
    {
        return [
            'type' => $this->determineContentType(),
            'agent' => $this->originalRequest->userAgent(),
            'method' => $this->originalRequest->method(),
            'token' => $this->originalRequest->bearerToken(),
            'full_url' => $this->originalRequest->fullUrl(),
            'url' => $this->originalRequest->url(),
            'format' => $this->originalRequest->format(),
            'query' => $this->originalRequest->query(),
            'params' => $this->originalRequest->all(),
        ];
    }

    /**
     * Determine the content type of the request.
     *
     * @return string
     */
    private function determineContentType()
    {
        if ($this->originalRequest->isJson()) {
            return 'json';
        }

        $contentType = $this->originalRequest->header('Content-Type');
        if (strpos($contentType, 'multipart') !== false) {
            return 'multipart';
        }
        if ($contentType === 'application/x-www-form-urlencoded') {
            return 'form';
        }

        return $contentType;
    }

    /**
     * Get the multipart parameters for the request.
     *
     * @return array
     */
    private function getMultipartParams()
    {
        $multipartParams = [];

        if ($this->originalRequest->isMethod('post')) {
            $formParams = $this->originalRequest->all();
            $fileUploads = [];

            foreach ($formParams as $key => $param) {
                if ($param instanceof HttpUploadedFile) {
                    $fileUploads[$key] = $param;
                    unset($formParams[$key]);
                }
            }

            if (count($fileUploads) > 0) {
                foreach ($formParams as $key => $value) {
                    $multipartParams[] = ['name' => $key, 'contents' => $value];
                }
                foreach ($fileUploads as $key => $value) {
                    $multipartParams[] = [
                        'name' => $key,
                        'contents' => fopen($value->getRealPath(), 'r'),
                        'filename' => $value->getClientOriginalName(),
                        'headers' => ['Content-Type' => $value->getMimeType()]
                    ];
                }
            }
        }

        return $multipartParams;
    }

    /**
     * Check if the response is JSON.
     *
     * @param Response $response
     * @return bool
     */
    private function isJson(Response $response)
    {
        return strpos($response->header('Content-Type'), 'json') !== false;
    }
}