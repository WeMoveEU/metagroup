<?php

namespace KenSh\MetabaseApi\Resources;

use GuzzleHttp\Psr7\Request;
use KenSh\MetabaseApi\Exception\RequestApiException;
use Psr\Http\Message\ResponseInterface;

class Dashboard extends Resource
{
    /**
     * @param int $id
     * @return mixed|ResponseInterface
     * @throws RequestApiException
     */
    public function get(int $id)
    {
        $result = $this->processRequest(
            new Request('GET', sprintf('api/dashboard/%s', $id))
        );

        return $result;
    }
}