<?php

namespace UserNotes\Hook;

use SkinTemplate;
use Title;

class PersonalUrls {
    public static function callback( array &$personal_urls, Title $title, SkinTemplate $skin ) {
        // Quick surrogate to check whether user is logged in
        if( !isset( $personal_urls[ 'logout' ] ) ) {
            return;
        }

        $personal_urls = array_merge(
            array_slice( $personal_urls, 0, count( $personal_urls ) - 1, true ), [
                'usernotes' => [
                    'text' => wfMessage( 'usernotes-action' )->text(),
                    'href' => Title::newFromText( 'Special:UserNotes')->getLinkURL()
            ] ],
            array_slice( $personal_urls, count( $personal_urls ) - 1, 1, true )
        );
    }
}