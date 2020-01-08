<?php
namespace KenSh\MetabaseApi\Resources;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use KenSh\MetabaseApi\Common\Result;
use KenSh\MetabaseApi\Exception\RequestApiException;
use Psr\Http\Message\ResponseInterface;

abstract class Resource
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Resource constructor.
     * @param $client
     */
    function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param Request $request
     * @return mixed|ResponseInterface
     * @throws RequestApiException
     */
    public function processRequest(Request $request) : ResponseInterface
    {
        try {
            return $this->client->send($request);
        } catch (GuzzleException $e) {
            throw new RequestApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Request $request
     * @return PromiseInterface
     */
    public function processRequestAsync(Request $request) : PromiseInterface
    {
        $promise = $this->client->sendAsync($request);
        return $promise;
    }

}