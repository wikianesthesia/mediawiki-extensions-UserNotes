<?php


namespace UserNotes\Hook;

use UserNotes\UserNotes;
use RequestContext;

class ApiOpenSearchSuggest {
    public static function callback( &$results ) {
        # Make sure user has access to any usernotes titles already found by opensearch
        foreach( $results as $articleId => $result ) {
            if( isset( $result[ 'title' ] ) && UserNotes::isTitleUserNotesArticle( $result[ 'title' ] ) ) {
                if( !UserNotes::userCanReadUserNotesTitle( $result[ 'title' ] ) ) {
                    unset( $results[ $articleId ] );
                }
            }
        }

        $search = RequestContext::getMain()->getRequest()->getText( 'search' );

        $userNotesArticleTitles = UserNotes::searchUserNotesArticleTitles( $search );

        if( !$userNotesArticleTitles ) {
            return;
        }

        foreach( $userNotesArticleTitles as $userNotesArticleTitle ) {
            $results[ $userNotesArticleTitle->getArticleID() ] = [
                'title' => $userNotesArticleTitle,
                'redirect from' => null,
                'extract' => false,
                'extract trimmed' => false,
                'image' => false,
                'url' => wfExpandUrl( $userNotesArticleTitle->getFullURL(), PROTO_CURRENT ),
                'displaytitle' => UserNotes::getUserNotesArticleDisplayTitle( $userNotesArticleTitle->getPrefixedText() )
            ];
        }

        usort( $results, function( $a, $b ) {
            $displayTitleA = $a[ 'displaytitle' ] ?? $a[ 'title' ]->getPrefixedText();
            $displayTitleB = $b[ 'displaytitle' ] ?? $b[ 'title' ]->getPrefixedText();

            return ( $displayTitleA < $displayTitleB ) ? -1 : 1;
        } );
    }
}