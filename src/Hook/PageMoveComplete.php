<?php

namespace UserNotes\Hook;

use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use PracticeGroups\PracticeGroups;
use Status;
use Title;

class PageMoveComplete {
    public static function callback( LinkTarget $old, LinkTarget $new, UserIdentity $userIdentity, int $pageid, int $redirid, string $reason, RevisionRecord $revision  ) {
        if( $old->getNamespace() === NS_USERNOTES ) {
            # Prevent infinite recursion
            return;
        } elseif( $old->getNamespace() !== NS_MAIN || $new->getNamespace() !== NS_MAIN ) {
            return;
        }

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );

        $table = 'page';
        $vars = [
            'page_title'
        ];

        // These two like conditions will search for titles which are subpages with exactly one level of depth
        $conds = [
            'page_namespace' => NS_USERNOTES,
            'page_title' . $dbr->buildLike( $dbr->anyString(), '/' . $old->getDBkey() ),
            'page_title NOT' . $dbr->buildLike( $dbr->anyString(), '/', $dbr->anyString(), '/', $dbr->anyString() )
        ];

        $res = $dbr->select(
            $table,
            $vars,
            $conds,
            __METHOD__
        );

        foreach( $res as $row ) {
            $oldUserNotesTitle = Title::newFromText( $row->page_title, NS_USERNOTES );

            // Get any subpages for the existing title.
            // Getting now since not sure if getSubpages() will still work after the move.
            $oldUserNotesTitleSubpages = $oldUserNotesTitle->getSubpages();

            // Create a new title for the subpage which matches the new article title
            $newUserNotesTitle = Title::makeTitleSafe( NS_USERNOTES, $oldUserNotesTitle->getRootText() . '/' . $new->getText() );

            $moveResult = static::movePage( $oldUserNotesTitle, $newUserNotesTitle, $userIdentity );

            if( $moveResult && count( $oldUserNotesTitleSubpages ) ) {
                foreach( $oldUserNotesTitleSubpages as $oldUserNotesTitleSubpage ) {
                    $newUserNotesTitleSubpageText = str_replace(
                        $oldUserNotesTitleSubpage->getText(),
                        $newUserNotesTitle->getText(),
                        $oldUserNotesTitleSubpage->getText()
                    );

                    $newUserNotesTitleSubpage = Title::makeTitleSafe( NS_USERNOTES, $newUserNotesTitleSubpageText );

                    // Don't bother checking the result since we should keep trying the other subpages even if one fails.
                    static::movePage( $oldUserNotesTitleSubpage, $newUserNotesTitleSubpage, $userIdentity );
                }
            }
        }
    }

    protected static function movePage( Title $oldTitle, Title $newTitle, UserIdentity $userIdentity ): Status {
        $movePageFactory = MediaWikiServices::getInstance()->getMovePageFactory();
        $logger = LoggerFactory::getInstance( PracticeGroups::getExtensionName() );

        $movePage = $movePageFactory->newMovePage( $oldTitle, $newTitle );
        $moveValidResult = $movePage->isValidMove();

        $loggerContext = [
            'old' => $oldTitle->getFullText(),
            'new' => $newTitle->getFullText(),
        ];

        // Make sure the move is valid (will check to make sure new title doesn't already exist)
        if( $moveValidResult->isOK() ) {
            $logger->debug( 'Attempting to move page from {old} to {new} to preserve main title link', $loggerContext );

            // Move the subpage. Redirects make things complicated (e.g. can't move and then move back)
            return $movePage->move( $userIdentity, wfMessage( 'practicegroups-move-reason-maintitlemoved' )->text(), false );
        } else {
            $loggerContext[ 'details' ] = $moveValidResult->getMessage()->text();

            $logger->error( 'Could not move subpage from {old} to {new}: {details}', $loggerContext );

            return $moveValidResult;
        }
    }
}