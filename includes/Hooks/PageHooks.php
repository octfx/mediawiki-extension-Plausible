<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Hooks;

use Config;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\Plausible;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Page\Hook\ArticleDeleteAfterSuccessHook;
use MediaWiki\Page\Hook\ArticleUndeleteHook;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use OutputPage;
use RequestContext;
use Skin;

/**
 * Hooks to run relating the page
 */
class PageHooks implements BeforePageDisplayHook, PageSaveCompleteHook, ArticleDeleteAfterSuccessHook, ArticleUndeleteHook, PageMoveCompleteHook {

	private array $config;
	private JobQueueGroup $jobs;

	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config->get( 'PlausibleServerSideTracking' );
		$this->jobs = $group;
	}

	/**
	 * Adds the tracking script to the page
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$plausible = new Plausible( $out );
		$plausible->addScript();
		$plausible->addModules();

		if ( $this->config['pageview'] && $out->getTitle()->exists() ) {
			$this->jobs->push(
				PlausibleEventJob::newFromRequest( $out->getRequest() )
			);
		}

		if ( $this->config['page404'] && !$out->getTitle()->exists() ) {
			$this->jobs->push(
				PlausibleEventJob::newFromRequest( $out->getRequest(), '404' )
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleDeleteAfterSuccess( $title, $outputPage ): void {
		if ( !$this->config['pagedelete'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( $outputPage->getRequest(), 'pagedelete' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ): void {
		if ( !$this->config['pageedit'] || $editResult->isNullEdit() ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			$user->getRequest(),
			'pageedit',
			[
				'title' => $wikiPage->getTitle()->getText(),
				'user' => $user->isRegistered() ? $user->getName() : null,
			]
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleUndelete( $title, $create, $comment, $oldPageId, $restoredPages ): void {
		if ( !$this->config['pageundelete'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( RequestContext::getMain()->getRequest(), 'pageedit' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onPageMoveComplete( $old, $new, $user, $pageid, $redirid, $reason, $revision ): void {
		if ( !$this->config['pagemove'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( $user->getRequest(), 'pagemove' ) );
	}
}
