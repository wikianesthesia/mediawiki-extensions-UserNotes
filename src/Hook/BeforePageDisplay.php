<?php

namespace UserNotes\Hook;

use MediaWiki\MediaWikiServices;
use OutputPage;
use Skin;
use Title;
use UserNotes\UserNotes;

class BeforePageDisplay {
    public static function callback( OutputPage &$out, Skin &$skin ) {
        $out->addModules( 'ext.userNotes.searchSuggest' );

        $title = $out->getTitle();

        if( $title->getNamespace() == NS_USERNOTES ) {
            if( $title->isSubpage() ) {
                $out->setPageTitle( UserNotes::getUserNotesArticleDisplayTitle( UserNotes::getMainArticleTitle( $title )->getText() ) );

                $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

                $out->setSubtitle( wfMessage( 'backlinksubtitle' )
                    ->rawParams( $linkRenderer->makeLink( Title::newFromText( 'Special:MyNotes' ), wfMessage( 'usernotes-backlink' )->text() ) ) );
            }
        }
    }
}