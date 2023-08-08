<?php

namespace MediaWiki\Extension\Plausible;

use InvalidArgumentException;
use MediaWiki\Extension\PageViewInfo\PageViewService;
use MediaWiki\MediaWikiServices;
use MultiHttpClient;
use MWTimestamp;
use StatusValue;
use Title;

class PlausiblePageViewService implements PageViewService
{

    private MultiHttpClient $client;
    private \Config $config;

    public function __construct()
    {
        $this->client = MediaWikiServices::getInstance()->getHttpRequestFactory()
            ->createMultiClient( [ 'maxConnsPerHost' => 8, 'usePipelining' => true ] );

        $this->config = MediaWikiServices::getInstance()->getMainConfig();
    }

    /**
     * @inheritDoc
     */
    public function supports($metric, $scope): bool
    {
        return in_array( $metric, [ self::METRIC_VIEW, self::METRIC_UNIQUE ] ) &&
            in_array( $scope, [ self::SCOPE_ARTICLE, self::SCOPE_TOP, self::SCOPE_SITE ] );
    }

    /**
     * @param Title[] $titles
     * @param int $days The number of days.
     * @param string $metric One of the METRIC_* constants.
     * @return StatusValue A status object with the data. Its success property will contain
     *    per-title success information.
     */
    public function getPageData(array $titles, $days, $metric = self::METRIC_VIEW)
    {
        if ( !in_array( $metric, [ self::METRIC_VIEW, self::METRIC_UNIQUE ] ) ) {
            throw new InvalidArgumentException( 'Invalid metric: ' . $metric );
        }

        $metric = $metric === self::METRIC_UNIQUE ? 'visitors' : 'visits';

        $query = [
            'site_id' => $this->config->get('PlausibleDomainKey'),
            'period' => '1d',
            'metrics' => $metric,
        ];

        $status = \Status::newGood();
        $urls = [];

        $client = MediaWikiServices::getInstance()->getHttpRequestFactory()->createMultiClient();

        foreach ($titles as $title) {
            $query['filters'] = sprintf('event:page==%s', $title->getLocalURL());

            $urls[] = [
                'method' => 'POST',
                'url' => sprintf(
                    '%s/api/v1/stats/timeseries%s',
                    $this->config->get('PlausibleDomain'),
                    http_build_query($query),
                ),
                'headers' => [
                    'Authorization' => 'Bearer: ',
                ]
            ];


        }

        $data = $client->runMulti($urls);

        $return = [];

        foreach ($data as $i => $response) {
            [ $code, $reason, $headers, $body, $error ] = $response['response'];

            if ($code !== 200) {
                $return[$titles[$i]->getPrefixedDBkey()] = null;
            } else {
                $body = json_decode($body, true);
                $return[$titles[$i]->getPrefixedDBkey()] = $body['results'][$metric]['value'];
            }
        }

        return $status->setResult(true, $return);
    }

    /**
     * @inheritDoc
     */
    public function getSiteData($days, $metric = self::METRIC_VIEW)
    {
        if ( !in_array( $metric, [ self::METRIC_VIEW, self::METRIC_UNIQUE ] ) ) {
            throw new InvalidArgumentException( 'Invalid metric: ' . $metric );
        }

        $metric = $metric === self::METRIC_UNIQUE ? 'visitors' : 'visits';

        $query = http_build_query([
            'site_id' => $this->config->get('PlausibleDomainKey'),
            'period' => '30d',
            'metrics' => $metric,
        ]);

        MediaWikiServices::getInstance()->getHttpRequestFactory()->post(
            sprintf('%s/api/v1/stats/timeseries%s', $this->config->get('PlausibleDomain'), $query),
            [
                'headers' => [
                    'Authorization' => 'Bearer: ',
                ]
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getTopPages($metric = self::METRIC_VIEW)
    {
        if ( !in_array( $metric, [ self::METRIC_VIEW, self::METRIC_UNIQUE ] ) ) {
            throw new InvalidArgumentException( 'Invalid metric: ' . $metric );
        }

        $metric = $metric === self::METRIC_UNIQUE ? 'visitors' : 'visits';

        $query = http_build_query([
            'site_id' => $this->config->get('PlausibleDomainKey'),
            'period' => '1d',
            'property' => 'event:page',
            'metrics' => $metric,
            'limit' => 10,
        ]);

        MediaWikiServices::getInstance()->getHttpRequestFactory()->post(
            sprintf('%s/api/v1/stats/breakdown%s', $this->config->get('PlausibleDomain'), $query),
            [
                'headers' => [
                    'Authorization' => 'Bearer: ',
                ]
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getCacheExpiry($metric, $scope): int
    {
        // data is valid until the end of the day
        $endOfDay = strtotime( '0:0 next day', MWTimestamp::time() );
        return $endOfDay - time();
    }
}