<?php

namespace UserNotes\Hook;

class ChangesListSpecialPageQuery {
    public static function callback( $name, &$tables, &$fields, &$conds, &$query_options, &$join_conds, $opts ) {
        // TODO make this more nuanced with permissions
        $conds[] = 'rc_namespace != ' . wfGetDB( DB_REPLICA)->addQuotes( NS_USERNOTES );
    }
}
