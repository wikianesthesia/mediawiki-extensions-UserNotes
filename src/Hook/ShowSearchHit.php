<?php


namespace UserNotes\Hook;

use BootstrapUI\BootstrapUI;
use HtmlArmor;
use MediaWiki\MediaWikiServices;
use UserNotes\UserNotes;
use RequestContext;
use SearchResult;
use SpecialSearch;

class ShowSearchHit {
    public static function callback( SpecialSearch $searchPage, SearchResult $result, $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html ) {
        $title = $result->getTitle();
        $titleText = $title->getText();

        if( UserNotes::isTitleUserNotesArticle( $title ) ) {
            $user = RequestContext::getMain()->getUser();

            if( !$user->isRegistered() || $user->getName() != $title->getRootText() ) {
                return false;
            }

            if( !$title->isSubpage() ) {
                return false;
            }

            $mainArticleTitle = UserNotes::getMainArticleTitle( $title );

            if( $mainArticleTitle ) {
                $titleText = $mainArticleTitle->getText();
            }

            $searchQuery = $searchPage->getRequest()->getText('search');
            $searchWords = explode( ' ', $searchQuery );

            foreach( $searchWords as $searchWord ) {
                $titleText = preg_replace( '/(' . preg_quote( $searchWord, '/' ) . ')/i', '<span class="searchmatch">$1</span>', $titleText );
            }

            $badgeHtml = BootstrapUI::badgeWidget( [
                'class' => 'usernotes-searchhit-badge',
            ], wfMessage( 'usernotes-personalnotes' )->text() );

            $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

            $link = $linkRenderer->makeKnownLink( $title, new HtmlArmor( $titleText . $badgeHtml ) );
        }
    }
}