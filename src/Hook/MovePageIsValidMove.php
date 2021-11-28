<?php

namespace UserNotes\Hook;

use Status;
use Title;
use UserNotes\UserNotes;

class MovePageIsValidMove {
    public static function callback( Title $oldTitle, Title $newTitle, Status $status ) {
        global $wgUserNotesEnabledNamespaces, $wgUserNotesBlacklistTitles;

        if( $oldTitle->getNamespace() == NS_USERNOTES && $newTitle->getNamespace() != NS_USERNOTES ) {
            $status->fatal( 'usernotes-error-move-fromnamespace' );
        } elseif( $oldTitle->getNamespace() != NS_USERNOTES && $newTitle->getNamespace() == NS_USERNOTES ) {
            $status->fatal( 'usernotes-error-move-tonamespace' );
        } elseif( $oldTitle->getNamespace() == NS_USERNOTES ) {
            if( $oldTitle->isSubpage() ) {
                if( !$newTitle->isSubpage() ) {
                    $status->fatal( 'usernotes-error-move-notsubpage' );
                } else {
                    $newParentTitle = UserNotes::getMainArticleTitle( $newTitle );

                    $newParentTitleNamespaceText = $newParentTitle->getNsText() ? $newParentTitle->getNsText() : 'Main';

                    if( !in_array( $newParentTitleNamespaceText, $wgUserNotesEnabledNamespaces ) ) {
                        $status->fatal( wfMessage( 'usernotes-error-mainarticlenotenablednamespace', $newParentTitleNamespaceText ) );
                    } elseif( in_array( $newParentTitle->getDBkey(), $wgUserNotesBlacklistTitles ) ) {
                        $status->fatal( wfMessage( 'usernotes-error-mainarticletitleblacklisted', $newParentTitle->getText() ) );
                    }
                }
            } else {
                # This should never happen since non-subpages should not be able to exist in the UserNotes namespace

                $status->fatal( 'usernotes-error-permissiondenied' );
            }
        }
    }
}