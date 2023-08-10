<?php

namespace MediaWiki\Extension\Plausible\Hooks;

use Config;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Hook\FileDeleteCompleteHook;
use MediaWiki\Hook\FileUndeleteCompleteHook;
use MediaWiki\Hook\UploadCompleteHook;
use RequestContext;

class FileHooks implements UploadCompleteHook, FileDeleteCompleteHook, FileUndeleteCompleteHook {

	private Config $config;
	private JobQueueGroup $jobs;

	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config;
		$this->jobs = $group;
	}

	/**
	 * @inheritDoc
	 */
	public function onFileDeleteComplete( $file, $oldimage, $article, $user, $reason ) {
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['filedelete'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( $user->getRequest(), 'filedelete' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onUploadComplete( $uploadBase ) {
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['fileupload'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( RequestContext::getMain()->getRequest(), 'fileupload' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onFileUndeleteComplete( $title, $fileVersions, $user, $reason ) {
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['fileundelete'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( $user->getRequest(), 'fileundelete' ) );
	}
}
