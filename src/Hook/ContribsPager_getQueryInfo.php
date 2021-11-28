<?php


namespace UserNotes\Hook;

use ContribsPager;

class ContribsPager_getQueryInfo {
    public static function callback( ContribsPager &$pager, array &$queryInfo ) {
        // TODO make this more nuanced with permissions
        $queryInfo[ 'conds' ][] = 'page_namespace != ' . wfGetDB( DB_REPLICA )->addQuotes( NS_USERNOTES );
    }
}