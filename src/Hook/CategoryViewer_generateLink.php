<?php


namespace UserNotes\Hook;

use Html;
use MediaWiki\MediaWikiServices;
use UserNotes\UserNotes;
use Title;

class CategoryViewer_generateLink {
    public static function callback( string $type, Title $title, ?string $html, ?string &$link ) {
        if( UserNotes::isTitleUserNotesArticle( $title ) ) {
            if( !UserNotes::userCanReadUserNotesTitle( $title ) ) {
                $link = Html::element( 'i', [], wfMessage( 'usernotes-linkprivate' )->text() );

                return;
            }

            // TODO this reformatted title won't get sorted under the correct letter. Would need some sort of hook
            // in CategoryViewer::addPage() to fix it.
            $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
            $link = $linkRenderer->makeLink( $title, UserNotes::getUserNotesArticleDisplayTitle( $title) );
        }
    }
}