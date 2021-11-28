<?php

namespace UserNotes\Hook;

use OutputPage;
use ParserOutput;
use UserNotes\UserNotes;

class OutputPageParserOutput {
    public static function callback( OutputPage $out, ParserOutput $parserOutput ) {
        if( $parserOutput->getProperty( 'nopersonalnotes' ) === false ) {
            UserNotes::setTabs();
        }
    }
}