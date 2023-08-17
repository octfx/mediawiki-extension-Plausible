<?php

namespace MediaWiki\Extension\Plausible\Hooks;

use Config;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Hook\SpecialSearchGoResultHook;
use MediaWiki\Hook\SpecialSearchNogomatchHook;
use RequestContext;

class SearchHooks implements SpecialSearchNogomatchHook, SpecialSearchGoResultHook {

	private array $config;
	private JobQueueGroup $jobs;

	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config->get( 'PlausibleServerSideTracking' );
		$this->jobs = $group;
	}

	/**
	 * @inheritDoc
	 */
	public function onSpecialSearchNogomatch( &$title ): void {
		if ( !$this->config['searchnotfound'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			RequestContext::getMain()->getRequest(),
			'Search: Not Found',
			[
				'term' => $title->getText(),
			]
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function onSpecialSearchGoResult( $term, $title, &$url ): void {
		if ( !$this->config['searchfound'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			RequestContext::getMain()->getRequest(),
			'Search: Found',
			[
				'term' => $term,
				'title' => $title->getText(),
			]
		) );
	}
}
