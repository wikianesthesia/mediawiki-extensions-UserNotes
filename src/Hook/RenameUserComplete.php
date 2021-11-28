<?php

namespace UserNotes\Hook;

use MediaWiki\MediaWikiServices;
use RequestContext;
use Title;

class RenameUserComplete {
    public static function callback( int $uid, string $old, string $new ) {
        $oldUserNotesTitle = Title::newFromText( $old, NS_USERNOTES );

        if( $oldUserNotesTitle->hasSubpages() ) {
            $subpages = $oldUserNotesTitle->getSubpages();

            foreach( $subpages as $oldSubpageTitle ) {
                $newSubpageTitle = Title::makeTitleSafe( NS_USERNOTES, str_replace( $old, $new, $oldSubpageTitle->getText() ) );

                $movePage = MediaWikiServices::getInstance()->getMovePageFactory()->newMovePage( $oldSubpageTitle, $newSubpageTitle );

                $movePage->move( RequestContext::getMain()->getUser(), wfMessage( 'usernotes-move-reason-userrenamed' )->text(), false );
            }
        }
    }
}
