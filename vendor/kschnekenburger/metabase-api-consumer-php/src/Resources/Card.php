<?php
namespace KenSh\MetabaseApi\Resources;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use KenSh\MetabaseApi\Common\Result;
use KenSh\MetabaseApi\Exception\RequestApiException as RequestApiExceptionAlias;

class Card extends Resource
{
    /**
     * @param int $id
     * @return Result
     * @throws RequestApiExceptionAlias
     */
    public function get(int $id)
    {
        $result = $this->processRequest(
            new Request('GET', sprintf('api/card/%s', $id))
        );

        return new Result(
            \GuzzleHttp\json_decode($result->getBody()->getContents())
        );
    }

    /**
     * @param int $id
     * @return PromiseInterface
     */
    public function getAsync(int $id)
    {
       return $this->processRequestAsync(new Request('GET', sprintf('api/card/%s', $id)));
    }

    /**
     * @param int $id
     * @param array $parameters
     * @return PromiseInterface
     */
    public function queryAsync(int $id, $parameters = [])
    {
        $processedParameters = null;
        if (!empty($parameters)) {
            $processedParameters = sprintf('?parameters=%s', json_encode($parameters));
        }

        return $this->processRequestAsync(
            new Request('POST', sprintf('api/card/%s/query/json%s', $id, $processedParameters))
        );
    }

    /**
     * @param int $id
     * @return mixed
     * @throws RequestApiExceptionAlias
     */
    public function getRelated(int $id)
    {
        $result = $this->processRequest(
            new Request('GET', sprintf('api/card/related/%s', $id))
        );

        return \GuzzleHttp\json_decode($result->getBody()->getContents());
    }
}