<?php
namespace KenSh\MetabaseApi\Resources;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use KenSh\MetabaseApi\Exception\RequestApiException;

/**
 * Class Activity
 * @package KenSh\MetabaseApi\Resources
 */
class Activity extends Resource
{
    /**
     * @link https://github.com/metabase/metabase/blob/master/docs/api-documentation.md#get-apiactivity
     * get full activity (Get recent activity)
     * @return mixed
     * @throws RequestApiException
     * @todo when PHP 7.4 leaked -> change GuzzleHttp\json_decode to json_decode(PHP Core)
     */
    public function getFullActivity()
    {
        $result = $this->processRequest(
            new Request('GET', 'api/activity')
        );

        return \GuzzleHttp\json_decode($result->getBody()->getContents());
    }

    /**
     * @link https://github.com/metabase/metabase/blob/master/docs/api-documentation.md#get-apiactivityrecent_views
     *
     * @return mixed
     * @throws RequestApiException
     * @todo when PHP 7.4 leaked -> change GuzzleHttp\json_decode to json_decode(PHP Core)
     */
    public function getRecentViews()
    {
        $result = $this->processRequest(
            new Request('GET', 'api/activity/recent_views')
        );

        return \GuzzleHttp\json_decode($result->getBody()->getContents());
    }
}