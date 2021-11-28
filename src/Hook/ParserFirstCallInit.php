<?php
namespace UserNotes\Hook;

use Parser;
use RequestContext;
use Title;

class ParserFirstCallInit {
    public static function callback( Parser $parser ) {
        $requestContext = RequestContext::getMain();

        if( $requestContext->hasTitle() ) {
            $title = $requestContext->getTitle();

            if( $title && $title->getNamespace() == NS_USERNOTES && !$title->isSubpage() ) {
                $requestContext->getOutput()->redirect( Title::newFromText( 'Special:MyNotes' )->getFullURL() );
            }
        }
    }
}