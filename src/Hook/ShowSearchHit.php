<?php


namespace UserNotes\Hook;

use UserNotes\UserNotes;
use RequestContext;
use SearchResult;
use SpecialSearch;

class ShowSearchHit {
    public static function callback( SpecialSearch $searchPage, SearchResult $result, $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html ) {
        if( UserNotes::isTitleUserNotesArticle( $result->getTitle() ) ) {
            $user = RequestContext::getMain()->getUser();

            if( !$user->isRegistered() || $user->getName() != $result->getTitle()->getRootText() ) {
                return false;
            }

            $resultTitle = $result->getTitle();

            if( !$resultTitle->isSubpage() ) {
                return false;
            }

            $mainArticleTitle = UserNotes::getMainArticleTitle( $resultTitle );

            if( $mainArticleTitle ) {
                $titleText = UserNotes::getUserNotesArticleDisplayTitle( $mainArticleTitle );

                $link = preg_replace('/^(.*>)UserNotes:([\w.-]+)\/(.*)(<.*)$/', '$1' . $titleText . '$4', $link );
            }
        }
    }
}