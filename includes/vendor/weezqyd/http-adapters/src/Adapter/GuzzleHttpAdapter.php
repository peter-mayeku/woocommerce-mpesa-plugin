<?php
/*
 *   This file is part of the HTTP Adapters library.
 *
 *   (c) Albert Leitato <wizqydy@gmail.com>
 *
 *   For the full copyright and license information, please view the LICENSE
 *   file that was distributed with this source code.
 */
namespace Http\Adapter;

use Http\Exceptions\HttpException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

/**
 * @author Marcos Sigueros <alrik11es@gmail.com>
 * @author Chris Fidao <fideloper@gmail.com>
 * @author Graham Campbell <graham@alt-three.com>
 */
class GuzzleHttpAdapter implements AdapterInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Response|ResponseInterface
     */
    public $response;

    /**
     * @param array                $token
     * @param ClientInterface|null $client
     */
    public function __construct(array $options, ClientInterface $client = null)
    {
        if (\version_compare(ClientInterface::VERSION, '6') === 1) {
            $this->client = $client ?: new Client($options);
        } else {
            $this->client = $client ?: new Client();
            foreach ($options as $key => $option) {
                $this->client->setDefaultOption($key, $option);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($url)
    {
        try {
            $this->response = $this->client->get($url);
        } catch (RequestException $e) {
            $this->exception = $e;
            $this->response  = $e->getResponse();
            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($url)
    {
        try {
            $this->response = $this->client->delete($url);
        } catch (RequestException $e) {
            $this->exception = $e;
            $this->response  = $e->getResponse();
            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function put($url, $content = '')
    {
        $options = [];

        $options[\is_array($content) ? 'json' : 'body'] = $content;

        try {
            $this->response = $this->client->put($url, $options);
        } catch (RequestException $e) {
            $this->exception = $e;
            $this->response  = $e->getResponse();
            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function post($url, $content = '')
    {
        $options = [];

        $options[\is_array($content) ? 'json' : 'body'] = $content;

        try {
            $this->response = $this->client->post($url, $options);
        } catch (RequestException $e) {
            $this->exception = $e;
            $this->response  = $e->getResponse();
            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestResponseHeaders()
    {
        if (null === $this->response) {
            return;
        }

        return [
            'reset'     => (int) (string) $this->response->getHeader('RateLimit-Reset'),
            'remaining' => (int) (string) $this->response->getHeader('RateLimit-Remaining'),
            'limit'     => (int) (string) $this->response->getHeader('RateLimit-Limit'),
        ];
    }

    /**
     * @throws HttpException
     */
    protected function handleError()
    {
        if ($this->exception instanceof ConnectException) {
            throw new HttpException($this->exception->getMessage(), 500);
        }
        $body = (string) $this->response->getBody();
        $code = (int) $this->response->getStatusCode();

        $content = \json_decode($body);

        throw new HttpException(isset($content->message) ? $content->message : $this->exception->getMessage(), $code);
    }
}
