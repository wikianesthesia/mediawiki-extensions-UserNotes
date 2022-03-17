<?php

namespace UserNotes\Hook;

use ApiMain;
use FauxRequest;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use Title;
use UserNotes\UserNotes;

class PageMoveComplete {
    public static function callback( LinkTarget $old, LinkTarget $new, UserIdentity $userIdentity, int $pageid, int $redirid, string $reason, RevisionRecord $revision  ) {
        $movePageFactory = MediaWikiServices::getInstance()->getMovePageFactory();
        $logger = LoggerFactory::getInstance( UserNotes::getExtensionName() );

        // Prevent infinite recursion
        if( $old->getNamespace() == NS_USERNOTES ) {
            return;
        }

        // TODO can this be done without FauxRequesting the API?
        // Currently this fetches all pages within the UserNotes namespace which could be expensive.
        $resultData = [
            'continue' => [
                'apcontinue' => ''
            ]
        ];

        while( isset( $resultData[ 'continue' ] ) ) {
            $request = [
                'action' => 'query',
                'list' => 'allpages',
                'apnamespace' => NS_USERNOTES,
                'apcontinue' => $resultData[ 'continue' ][ 'apcontinue' ],
                'aplimit' => 'max'
            ];

            $fauxReq = new FauxRequest( $request );

            $module = new ApiMain( $fauxReq );
            $module->execute();

            $resultData = $module->getResult()->getResultData();
            $pageData = $module->getResult()->getResultData( [ 'query', 'allpages' ] );

            // Iterate through all pages in the UserNotes namespace to find subpages which match the moved article title
            foreach( $pageData as $pageResult ) {
                if( isset( $pageResult[ 'pageid' ] ) ) {
                    $userNotesTitle = Title::newFromID( $pageResult[ 'pageid' ] );

                    // Only subpages are relevant since they might match the moved article title
                    if( $userNotesTitle->isSubpage() ) {
                        // See if the subpage text matches the old article title
                        if( $old->getText() == UserNotes::getMainArticleTitle( $userNotesTitle ) ) {
                            // Create a new title for the subpage which matches the new article title
                            $newUserNotesTitle = Title::makeTitleSafe( NS_USERNOTES, $userNotesTitle->getRootText() . '/' . $new->getText() );

                            $movePage = $movePageFactory->newMovePage( $userNotesTitle, $newUserNotesTitle );

                            $moveValidResult = $movePage->isValidMove();

                            if( !$newUserNotesTitle->exists() && $moveValidResult->isOK() ) {
                                // Move the subpage
                                $movePage->move( $userIdentity, wfMessage( 'usernotes-move-reason-mainarticlemoved' )->text(), false );
                            } else {
                                # TODO i18n
                                $errorDetails = '';

                                if( $newUserNotesTitle->exists() ) {
                                    $errorDetails = 'New page already exists';
                                } elseif( !$moveValidResult->isOK() ) {
                                    $errorDetails = $moveValidResult->getMessage()->text();
                                }

                                $logger->warning( 'Could not move subpage from {old} to {new}: {details}', [
                                    'old' => $old->getText(),
                                    'new' => $new->getText(),
                                    $errorDetails
                                ] );
                            }
                        }
                    }
                }
            }
        }
    }
}