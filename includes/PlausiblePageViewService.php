<?php

namespace MediaWiki\Extension\Plausible;

use Config;
use Exception;
use InvalidArgumentException;
use JsonException;
use MediaWiki\Extension\PageViewInfo\PageViewService;
use MediaWiki\MediaWikiServices;
use MWTimestamp;
use Psr\Log\NullLogger;
use RuntimeException;
use StatusValue;
use Title;

class PlausiblePageViewService implements PageViewService {
	private Config $config;

	/** @var int UNIX timestamp of 0:00 of the last day with complete data */
	protected $lastCompleteDay;

	/** @var array Cache for getEmptyDateRange() */
	protected $range;

	public function __construct() {
		$this->config = MediaWikiServices::getInstance()->getMainConfig();

		// Skip the current day for which only partial information is available
		$this->lastCompleteDay = strtotime( '0:0 1 day ago', MWTimestamp::time() );

		$this->logger = new NullLogger();
	}

	/**
	 * @inheritDoc
	 */
	public function supports( $metric, $scope ): bool {
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
	public function getPageData( array $titles, $days, $metric = self::METRIC_VIEW ): StatusValue {
		if ( !in_array( $metric, [ self::METRIC_VIEW, self::METRIC_UNIQUE ] ) ) {
			throw new InvalidArgumentException( 'Invalid metric: ' . $metric );
		}

		if ( $days <= 0 ) {
			throw new InvalidArgumentException( 'Invalid days: ' . $days );
		}

		$status = StatusValue::newGood();

		$urls = [];
		$metric = $metric === self::METRIC_UNIQUE ? 'visitors' : 'pageviews';
		$query = $this->makeBaseQuery( $metric, $days );

		$client = MediaWikiServices::getInstance()->getHttpRequestFactory()->createMultiClient();

		foreach ( $titles as $title ) {
			$query['filters'] = sprintf( 'event:page==%s', $title->getLocalURL() );

			$urls[] = [
				'method' => 'GET',
				'url' => sprintf(
					'%s/api/v1/stats/timeseries?%s',
					$this->config->get( 'PlausibleDomain' ),
					http_build_query( $query ),
				),
				'headers' => [
					'Authorization' => $this->getAuthHeaderValue(),
				],
			];
		}

		try {
			$data = $client->runMulti( $urls );
		} catch ( Exception $e ) {
			return StatusValue::newFatal( 'pvi-invalidresponse' );
		}

		$result = [];

		foreach ( $data as $i => $response ) {
			[ $code, $reason, $headers, $body, $error ] = $response['response'];
			$title = $titles[$i]->getPrefixedDBkey();
			$result[$title] = $this->getEmptyDateRange( $days );

			if ( $code == 200 ) {
				try{
					$body = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );

					foreach ( $body['results'] as $data ) {
						$result[$title][$data['date']] = $data[$metric];
					}
				} catch ( JsonException $e ) {
					continue;
				}
			}
		}

		return $status->setResult( true, $result );
	}

	/**
	 * @inheritDoc
	 */
	public function getSiteData( $days, $metric = self::METRIC_VIEW ) {
		if ( !in_array( $metric, [ self::METRIC_VIEW, self::METRIC_UNIQUE ] ) ) {
			throw new InvalidArgumentException( 'Invalid metric: ' . $metric );
		}

		if ( $days <= 0 ) {
			throw new InvalidArgumentException( 'Invalid days: ' . $days );
		}

		$metric = $metric === self::METRIC_UNIQUE ? 'visitors' : 'pageviews';

		$query = http_build_query( $this->makeBaseQuery( $metric, $days ) );

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()->create(
			sprintf( '%s/api/v1/stats/timeseries?%s', $this->config->get( 'PlausibleDomain' ), $query ),
			[
				'headers' => [
					'Authorization' => $this->getAuthHeaderValue(),
				]
			]
		);

		$request->setHeader( 'Authorization', $this->getAuthHeaderValue() );

		$status = $request->execute();

		if ( !$status->isOK() ) {
			return $status->fatal( 'pvi-invalidresponse' );
		}

		try {
			$result = json_decode( $request->getContent(), true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			$result = null;
		}

		$out = [];

		foreach ( $result['results'] as $pageMetric ) {
			$out[$pageMetric['date']] = $pageMetric[$metric];
		}

		$status->setResult( true, $out );

		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getTopPages( $metric = self::METRIC_VIEW ) {
		return $this->getTopPagesDays( 1, $metric );
	}

	/**
	 * This is getTopPages with a configurable day range
	 */
	public function getTopPagesDays( $days = 1, $metric = self::METRIC_VIEW ) {
		if ( !in_array( $metric, [ self::METRIC_VIEW, self::METRIC_UNIQUE ] ) ) {
			throw new InvalidArgumentException( 'Invalid metric: ' . $metric );
		}

		$metric = $metric === self::METRIC_UNIQUE ? 'visitors' : 'pageviews';

		$query = http_build_query( $this->makeBaseQuery( $metric, $days ) + [
			'property' => 'event:page',
			'limit' => 10,
		] );

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()->create( sprintf( '%s/api/v1/stats/breakdown?%s', $this->config->get( 'PlausibleDomain' ), $query ),
			[
				'headers' => [
					'Authorization' => $this->getAuthHeaderValue(),
				]
			]
		);

		$request->setHeader( 'Authorization', $this->getAuthHeaderValue() );

		$status = $request->execute();

		if ( !$status->isOK() ) {
			return $status->fatal( 'pvi-invalidresponse' );
		}

		try {
			$result = json_decode( $request->getContent(), true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			$result = null;
		}

		$out = [];

		foreach ( $result['results'] as $pageMetric ) {
			$out[self::pageTitleForMW( $pageMetric['page'] )] = $pageMetric[$metric];
		}

		$status->setResult( true, $out );

		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheExpiry( $metric, $scope ): int {
		// data is valid until the end of the day
		$endOfDay = strtotime( '0:0 next day', MWTimestamp::time() );
		return $endOfDay - time();
	}

	/**
	 * The pageview API omits dates if there is no data. Fill it with nulls to make client-side
	 * processing easier.
	 * @param int $days
	 * @return array YYYY-MM-DD => null
	 */
	protected function getEmptyDateRange( $days ) {
		if ( !$this->range ) {
			$this->range = [];
			// we only care about the date part, so add some hours to avoid errors when there is a
			// leap second or some other weirdness
			$end = $this->lastCompleteDay + 12 * 3600;
			$start = $end - ( $days - 1 ) * 24 * 3600;
			for ( $ts = $start; $ts <= $end; $ts += 24 * 3600 ) {
				$this->range[gmdate( 'Y-m-d', $ts )] = null;
			}
		}
		return $this->range;
	}

	/**
	 * Builds the bearer header
	 *
	 * @return string
	 */
	private function getAuthHeaderValue(): string {
		if ( empty( $this->config->get( 'PlausibleApiKey' ) ) ) {
			throw new RuntimeException( 'wgPlausibleApiKey is empty' );
		}
		return sprintf( 'Bearer %s', $this->config->get( 'PlausibleApiKey' ) );
	}

	/**
	 * Query params used in almost all requests
	 *
	 * @param string $metric
	 * @param int $days
	 * @return array
	 */
	private function makeBaseQuery( string $metric, int $days ): array {
		return [
			'site_id' => $this->config->get( 'PlausibleDomainKey' ),
			'period' => 'custom',
			'date' => sprintf(
				'%s,%s',
				gmdate( 'Y-m-d', strtotime( '0:0 ' . $days . ' day ago', MWTimestamp::time() ) ),
				gmdate( 'Y-m-d', strtotime( '0:0 today', MWTimestamp::time() ) )
			),
			'metrics' => $metric,
		];
	}

	/**
	 * @param string $title
	 * @return string title text converted MediaWiki-friendly
	 */
	protected static function pageTitleForMW( string $title ): string {
		$title = preg_replace( '/ - [^-]+$/', '', $title );
		$title = preg_replace( '/ /', '_', $title );

		return ltrim( $title, '/' );
	}
}
