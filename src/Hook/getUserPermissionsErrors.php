<?php

namespace UserNotes\Hook;

use Title;
use User;
use UserNotes\UserNotes;

class getUserPermissionsErrors {
    public static function callback( Title $title, User $user, $action, &$result ) {
        global $wgUserNotesEnabledNamespaces, $wgUserNotesBlacklistTitles;

        if( $title->getNamespace() == NS_USERNOTES ) {
            if( !$user->isRegistered() ) {
                $result = false;

                return false;
            } elseif( $user->getName() != $title->getRootText() ) {
                $result = 'usernotes-error-permissiondenied';

                return false;
            }

            if( !$title->isSubpage() && $action != 'read' ) {
                # Root pages are not allowed. 'read' is only allowed to allow ParserFirstCallInit to redirect.
                $result = false;

                return false;
            }

            if( $action == 'create' ) {
                $parentTitle = UserNotes::getMainArticleTitle( $title );

                $parentTitleNamespaceText = $parentTitle->getNsText() ? $parentTitle->getNsText() : 'Main';

                if( !in_array( $parentTitleNamespaceText, $wgUserNotesEnabledNamespaces ) ) {
                    $result = wfMessage( 'usernotes-error-create',
                        wfMessage( 'usernotes-error-mainarticlenotenablednamespace', $parentTitleNamespaceText )->text()
                    );

                    return false;
                } elseif( in_array( $parentTitle->getDBkey(), $wgUserNotesBlacklistTitles ) ) {
                    $result = wfMessage( 'usernotes-error-create',
                        wfMessage( 'usernotes-error-mainarticletitleblacklisted', $parentTitle->getText() )->text()
                    );

                    return false;
                }
            }
        }
    }
}