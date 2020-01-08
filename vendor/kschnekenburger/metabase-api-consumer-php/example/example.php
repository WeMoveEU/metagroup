<?php
require 'vendor/autoload.php';

use function GuzzleHttp\Promise\settle;
use KenSh\MetabaseApi\{Common\DateFilter, Exception\RequestApiException, Factory};

$metabase = Factory::create(
    '*****',
    '*****',
    '*****'
);

$queryParameters = [
    [
        'type' => DateFilter::TYPE_RELATIVE,
        'target' => ['dimension', ['template-tag','date']],
        'value' => DateFilter::VALUE_TODY
    ]
];

/**
 * get definition;
 */
/**
 *
 *  get definition of card
 *
 */

try {
    $result = $metabase->card()->get(143)->getVisualizationSettings()->getTableColumns();
} catch (RequestApiException $e) {

}

/**
 * get cards query (json) async.
 */

$promises = [
    $metabase->card()->queryAsync(143, $queryParameters),
    $metabase->card()->queryAsync(142, $queryParameters)
];

$resultPromises = settle($promises)->wait();