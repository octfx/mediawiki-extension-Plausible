<?php

namespace MediaWiki\Extension\Plausible\Hooks;

use Config;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Hook\SpecialSearchGoResultHook;
use MediaWiki\Hook\SpecialSearchNogomatchHook;
use RequestContext;

class SearchHooks implements SpecialSearchNogomatchHook, SpecialSearchGoResultHook {

	private Config $config;
	private JobQueueGroup $jobs;

	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config;
		$this->jobs = $group;
	}

	/**
	 * @inheritDoc
	 */
	public function onSpecialSearchNogomatch( &$title ) {
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['searchnotfound'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			RequestContext::getMain()->getRequest(),
			'searchnotfound',
			[
				'title' => $title->getText(),
			]
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function onSpecialSearchGoResult( $term, $title, &$url ) {
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['searchfound'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			RequestContext::getMain()->getRequest(),
			'searchfound',
			[
				'term' => $term,
				'title' => $title->getText(),
			]
		) );
	}
}
