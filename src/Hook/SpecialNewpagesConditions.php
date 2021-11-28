<?php


namespace UserNotes\Hook;

use FormOptions;
use NewPagesPager;

class SpecialNewpagesConditions {
    public static function callback( NewPagesPager &$pager, FormOptions $opts, array &$conds, array &$tables, array &$fields, array &$joinConds ) {
        // TODO make this more nuanced with permissions
        $conds[] = 'page_namespace != ' . wfGetDB( DB_REPLICA )->addQuotes( NS_USERNOTES );
    }
}