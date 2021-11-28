<?php

namespace UserNotes\Hook;

class GetDoubleUnderscoreIDs {
    public static function callback( array &$mDoubleUnderscoreIDs ) {
        $mDoubleUnderscoreIDs[] = 'nopersonalnotes';
    }
}