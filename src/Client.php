<?php

/*
 * This file is part of the overtrue/http.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\Http;

use GuzzleHttp\Client as GuzzleClient;
use Overtrue\Http\Responses\Response;
use Overtrue\Http\Traits\HasHttpRequests;

/**
 * Class BaseClient.
 *
 * @author overtrue <i@overtrue.me>
 */
class Client
{
    use HasHttpRequests { request as performRequest; }

    /**
     * @var \Overtrue\Http\Config
     */
    protected $config;

    /**
     * @var
     */
    protected $baseUri;

    /**
     * @return static
     */
    public static function create(): self
    {
        return new static(...func_get_args());
    }

    /**
     * Client constructor.
     *
     * @param \Overtrue\Http\Config|array $config
     */
    public function __construct($config = [])
    {
        $this->config = $this->normalizeConfig($config);
    }

    /**
     * GET request.
     *
     * @param string $url
     * @param array  $query
     *
     * @return \Psr\Http\Message\ResponseInterface|\Overtrue\Http\Support\Collection|array|object|string
     */
    public function get(string $url, array $query = [])
    {
        return $this->request($url, 'GET', ['query' => $query]);
    }

    /**
     * POST request.
     *
     * @param string $url
     * @param array  $data
     *
     * @return \Psr\Http\Message\ResponseInterface|\Overtrue\Http\Support\Collection|array|object|string
     */
    public function post(string $url, array $data = [])
    {
        return $this->request($url, 'POST', ['form_params' => $data]);
    }

    /**
     * JSON request.
     *
     * @param string       $url
     * @param string|array $data
     * @param array        $query
     *
     * @return \Psr\Http\Message\ResponseInterface|\Overtrue\Http\Support\Collection|array|object|string
     */
    public function postJson(string $url, array $data = [], array $query = [])
    {
        return $this->request($url, 'POST', ['query' => $query, 'json' => $data]);
    }

    /**
     * Upload file.
     *
     * @param string $url
     * @param array  $files
     * @param array  $form
     * @param array  $query
     *
     * @return \Psr\Http\Message\ResponseInterface|\Overtrue\Http\Support\Collection|array|object|string
     */
    public function upload(string $url, array $files = [], array $form = [], array $query = [])
    {
        $multipart = [];

        foreach ($files as $name => $path) {
            $multipart[] = [
                'name'     => $name,
                'contents' => fopen($path, 'r'),
            ];
        }

        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request($url, 'POST', ['query' => $query, 'multipart' => $multipart]);
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array  $options
     * @param bool   $returnRaw
     *
     * @return \Psr\Http\Message\ResponseInterface|\Overtrue\Http\Support\Collection|array|object|string
     */
    public function request(string $uri, string $method = 'GET', array $options = [], $returnRaw = false)
    {
        if (property_exists($this, 'baseUri') && !is_null($this->baseUri)) {
            $options['base_uri'] = $this->baseUri;
        }

        if ((!empty($options['base_uri']) || $this->config->getBaseUri()) && $this->config->needAutoTrimEndpointSlash()) {
            $uri = ltrim($uri, '/');
        }

        $response = $this->performRequest($uri, $method, $options);

        return $returnRaw ? $response : $this->castResponseToType($response, $this->config->getOption('response_type'));
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $options
     *
     * @return \Overtrue\Http\Responses\Response
     */
    public function requestRaw(string $url, string $method = 'GET', array $options = [])
    {
        return Response::buildFromPsrResponse($this->request($url, $method, $options, true));
    }

    /**
     * @param \GuzzleHttp\Client $client
     *
     * @return \Overtrue\Http\Client
     */
    public function setHttpClient(GuzzleClient $client): \Overtrue\Http\Client
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * Return GuzzleHttp\Client instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient(): \GuzzleHttp\Client
    {
        if (!($this->httpClient instanceof GuzzleClient)) {
            $this->httpClient = new GuzzleClient($this->config->toArray());
        }

        return $this->httpClient;
    }

    /**
     * @return \Overtrue\Http\Config
     */
    public function getConfig(): \Overtrue\Http\Config
    {
        return $this->config;
    }

    /**
     * @param \Overtrue\Http\Config $config
     *
     * @return \Overtrue\Http\Client
     */
    public function setConfig(\Overtrue\Http\Config $config): \Overtrue\Http\Client
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param mixed $config
     *
     * @return \Overtrue\Http\Config
     */
    protected function normalizeConfig($config): \Overtrue\Http\Config
    {
        if (\is_array($config)) {
            $config = new Config($config);
        }

        if (!($config instanceof Config)) {
            throw new \InvalidArgumentException('config must be array or instance of Overtrue\Http\Config.');
        }

        return $config;
    }
}
