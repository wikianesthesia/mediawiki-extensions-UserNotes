<?php


namespace UserNotes\Hook;

use DatabaseLogEntry;
use Html;
use LogEventsList;

class LogEventsListLineEnding {

    public static function callback( LogEventsList $page, string &$line, DatabaseLogEntry &$entry, array &$classes, array &$attribs ) {
        if( $entry->getTarget()->getNamespace() == NS_USERNOTES ) {
            $line = preg_replace( '/(<.*)/m', Html::rawElement( 'i', [], wfMessage( 'usernotes-logprivate' )->text() ), $line, 1 );
        }
    }
}