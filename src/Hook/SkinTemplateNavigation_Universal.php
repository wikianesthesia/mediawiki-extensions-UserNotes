<?php

namespace UserNotes\Hook;

use MediaWiki\MediaWikiServices;
use SkinTemplate;
use Title;
use UserNotes\UserNotes;

class SkinTemplateNavigation_Universal {
    public static function callback( SkinTemplate $skinTemplate, array &$links ) {
        return;

        global $wgArticlePath;
        global $wgUserNotesAddAction, $wgUserNotesBlacklistTitles, $wgUserNotesEnabledNamespaces, $wgUserNotesOtherNotesNamespaces;

        if( $skinTemplate->getUser()->isLoggedIn() ) {
            $title = $skinTemplate->getRelevantTitle();

            if( $title->getNamespace() == NS_USERNOTES ) {
                // If the current article is in the UserNotes namespace, set up the namespace links to
                // refer back to the main article and the main article's talk page.
                $mainArticleTitle = UserNotes::getMainArticleTitle( $title );

                $newNamespaceLinks = [ 'main' => [
                        'class' => '',
                        'text' => wfMessage( 'usernotes-mainarticle' )->text(),
                        'href' => $mainArticleTitle->getLinkURL(),
                        'exist' => $mainArticleTitle->exists(),
                        'primary' => true,
                        'context' => 'subject',
                        'id' => 'ca-nstab-main'
                    ]
                ];

                if( $mainArticleTitle->canHaveTalkPage() ) {
                    $talkTitle = Title::newFromText( 'Talk:' . $mainArticleTitle->getText() );

                    $newNamespaceLinks[ 'talk' ] = [
                        'class' => '',
                        'text' => wfMessage( 'talk' )->text(),
                        'href' => $talkTitle->getLinkURL(),
                        'exist' => $talkTitle->exists(),
                        'primary' => true,
                        'context' => 'talk',
                        'rel' => 'discussion',
                        'id' => 'ca-talk'
                    ];
                }

                $links[ 'namespaces' ] = $newNamespaceLinks + $links[ 'namespaces' ];

                if( isset( $links[ 'namespaces' ][ 'usernotes' ] ) ) {
                    unset( $links[ 'namespaces' ][ 'usernotes' ] );
                }

                if( isset( $links[ 'namespaces' ][ 'usernotes_talk' ] ) ) {
                    unset( $links[ 'namespaces' ][ 'usernotes_talk' ] );
                }

                # Set the title to the main article to add user notes links
                $title = $mainArticleTitle;
            } else {
                $titleNamespaceText = $title->getNsText() ? $title->getNsText() : 'Main';

                if( in_array( $titleNamespaceText, $wgUserNotesOtherNotesNamespaces )
                && $title->isSubpage() ) {
                    $title = UserNotes::getMainArticleTitle( $title );
                }
            }

            # If the current article is in an enabled namespace for UserNotes and is not a blacklisted title,
            # add the UserNotes subpage to the list of related namespace pages and optionally create an action

            # If the title is a talk page, refer to the main article
            if( $title->isTalkPage() ) {
                $subjectPage = MediaWikiServices::getInstance()->getNamespaceInfo()->getSubjectPage( $title );

                $title = Title::newFromText( $subjectPage->getText(), $subjectPage->getNamespace() );
            }

            $titleNamespaceText = $title->getNsText() ? $title->getNsText() : 'Main';

            if( in_array( $titleNamespaceText, $wgUserNotesEnabledNamespaces )
                && !in_array( $title->getDBkey(), $wgUserNotesBlacklistTitles ) ) {

                $userNotesTitleText = 'UserNotes:' . $skinTemplate->getUser()->getName() . '/' . $title->getPartialURL();

                $links[ 'namespaces' ][ 'usernotes' ] = [
                    'class' => '',
                    'text' => wfMessage( 'usernotes-action' )->text(),
                    'href' => str_replace( '$1', $userNotesTitleText, $wgArticlePath ),
                    'exists' => true,
                    'primary' => true,
                    'context' => 'usernotes'
                ];

                if( $wgUserNotesAddAction ) {
                    # TODO could this 'action's be something like 'tabs' to get handed around to the skin to deal with it differently
                    $links[ 'actions' ][ 'my_notes' ] = [
                        'class' => '',
                        'href' => str_replace( '$1', $userNotesTitleText, $wgArticlePath ),
                        'text' => wfMessage( 'usernotes-action' )->text()
                    ];
                }
            }
        }
    }
}