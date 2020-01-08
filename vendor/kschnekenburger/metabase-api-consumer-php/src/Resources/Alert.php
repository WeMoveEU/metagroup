<?php
namespace KenSh\MetabaseApi\Resources;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use KenSh\MetabaseApi\Exception\DeprecatedEndpointException;
use KenSh\MetabaseApi\Exception\NotImplementedException;

class Alert extends Resource
{

    /**
     * @param bool $archived
     * @param null $questionId
     * @return mixed
     * @throws GuzzleException
     */
    public function get($archived = false, $questionId = null)
    {
        $uri = 'api/alert';
        if ($questionId !== null) {
            $uri = 'api/alert/question/' . $questionId;
        }

        $req = new Request('GET', sprintf('%s?%s', $uri, http_build_query($archived)));
        $result = $this->client->send($req);

        return \GuzzleHttp\json_decode($result->getBody()->getContents());
    }

    /**
     * @throws NotImplementedException
     */
    public function create()
    {
        throw new NotImplementedException();
    }

    /**
     * @param $id alert id
     * @throws NotImplementedException
     */
    public function update($id)
    {
        throw new NotImplementedException();
    }

    /**
     * @param $id alert id
     * @throws DeprecatedEndpointException
     * @link https://github.com/metabase/metabase/blob/master/docs/api-documentation.md#delete-apialertid
     */
    public function delete($id)
    {
        throw new DeprecatedEndpointException('Delete an Alert. (DEPRECATED -- don\'t delete a Alert anymore -- archive it instead.)');
    }

    /**
     * @param $id alert id
     * @throws NotImplementedException
     */
    public function subscribe($id)
    {
        throw new NotImplementedException();
    }
}