<?php


namespace UserNotes\Hook;

use ApiBase;
use MediaWiki\MediaWikiServices;
use UserNotes\UserNotes;

class APIQueryAfterExecute {

    protected static $allowedResultKeys = [
        'ns',
        'pageid',
        'title'
    ];

    public static function callback( ApiBase &$module ) {
        $result = $module->getResult();
        $resultData = $result->getResultData();

        # For some reason when in generator mode, this can get called several times. It has something to do with
        # thumbnail processing. Regardless, I think it's probably fine to only do processing on the first call
        # and set a flag to skip subsequent calls.
        if( isset( $resultData[ '_usernotes' ] ) ) {
            return;
        }

        if( isset( $resultData[ 'query' ] ) ) {
            self::onPrefixSearch( $module );
            self::filterResultsPermissions( $module );
        }

        $result->addValue( [ '_usernotes' ], null, 1 );
    }



    public static function filterResultsPermissions( ApiBase &$module ) {
        $apiResult = $module->getResult();
        $resultData = $apiResult->getResultData();

        $user = $module->getUser();

        $userNotesNsText = MediaWikiServices::getInstance()->getNamespaceInfo()->getCanonicalName( NS_USERNOTES );

        foreach( $resultData[ 'query' ] as $list => $results ) {
            $path = [
                'query',
                $list
            ];

            foreach( $results as $resultKey => $result ) {
                if( isset( $result[ 'ns' ] )
                    && $result[ 'ns' ] == NS_USERNOTES ) {
                    $blockResult = true;

                    if( $user->isRegistered() ) {
                        if( preg_match('/^' . $userNotesNsText . ':(.*)(\/|$)/U', $result[ 'title' ], $userNameMatches ) && $userNameMatches[ 1 ] == $user->getName() ) {
                            $blockResult = false;
                        }
                    }

                    if( $blockResult ) {
                        $apiResult->removeValue( $path, $resultKey );

                        # It is important that any user be able to at least see that the page exists through the api
                        # to handle things like page moves.

                        $allowedResult = [];

                        foreach( self::$allowedResultKeys as $allowedResultKey ) {
                            $allowedResult[ $allowedResultKey ] = $result[ $allowedResultKey ];
                        }

                        $apiResult->addValue( [ 'query', $list ], null, $allowedResult );
                    }
                }
            }
        }
    }



    /**
     * This function adds practice group articles to search results when using prefixsearch
     *
     * @param ApiBase $module
     */
    public static function onPrefixSearch( ApiBase &$module ) {
        $request = $module->getRequest();
        $result = $module->getResult();
        $resultData = $result->getResultData();

        # The result path is different depending on whether queries from list or generator
        $reqList = $request->getText( 'list' );
        $reqGenerator = $request->getText( 'generator' );

        if( $reqList !== 'prefixsearch' && $reqGenerator !== 'prefixsearch' ) {
            return;
        }

        if( $reqGenerator ) {
            $generator = true;
            $paramPrefix = 'gps';
            $moduleName = 'pages';
        } else {
            $generator = false;
            $paramPrefix = 'ps';
            $moduleName = 'prefixsearch';
        }

        $results = $resultData[ 'query' ][ $moduleName ];
        unset( $results[ '_element' ] );
        unset( $results[ '_type' ] );

        $search = $request->getText( $paramPrefix . 'search' );
        $limit = $request->getText( $paramPrefix . 'limit' );

        # TODO implement limit/continue
        if( $limit === 'max' ) {
            $limit = 5000;
        } elseif( !$limit || $limit < 1 || $limit > 5000 ) {
            $limit = 10;
        }

        $userNotesArticleTitles = UserNotes::searchUserNotesArticleTitles( $search );

        if( !$userNotesArticleTitles ) {
            return;
        }

        if( $generator ) {
            foreach( $userNotesArticleTitles as $userNotesArticleTitle ) {
                $results[ $userNotesArticleTitle->getArticleID()] = [
                    'pageid' => $userNotesArticleTitle->getArticleID(),
                    'ns' => NS_USERNOTES,
                    'title' => $userNotesArticleTitle->getPrefixedText(),
                    'index' => count( $results ) + 1,
                    'displaytitle' => UserNotes::getUserNotesArticleDisplayTitle( $userNotesArticleTitle->getPrefixedText() )
                ];
            }

            # Sort by title. This removes some of the smarts of the underlying search, but it's not a huge deal.
            $resultsSort = [];

            foreach( $results as $articleId => $resultTitle ) {
                $resultsSort[ $articleId ] = $resultTitle[ 'displaytitle' ] ?? $resultTitle[ 'title' ];
            }

            asort( $resultsSort );
            $index = 1;

            foreach( $resultsSort as $articleId => $displayTitle ) {
                $results[ $articleId ][ 'index' ] = $index;
                $index++;
            }
        } else {
            foreach( $userNotesArticleTitles as $userNotesArticleTitle ) {
                $results[] = [
                    'ns' => NS_USERNOTES,
                    'title' => $userNotesArticleTitle->getPrefixedText(),
                    'pageid' => $userNotesArticleTitle->getArticleID(),
                    'displaytitle' => UserNotes::getUserNotesArticleDisplayTitle( $userNotesArticleTitle->getPrefixedText() )
                ];
            }

            usort( $results, function( $a, $b ) {
                $displayTitleA = $a[ 'displaytitle' ] ?? $a[ 'title' ];
                $displayTitleB = $b[ 'displaytitle' ] ?? $b[ 'title' ];

                return ( $displayTitleA < $displayTitleB ) ? -1 : 1;
            } );
        }

        # TODO this breaks thumbnails
        //$results = array_slice( $results, 0, $limit );

        $result->reset();

        $result->addValue( [ 'query' ], $moduleName, $results );
    }
}