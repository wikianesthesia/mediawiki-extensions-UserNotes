( function () {
    function escapeQuery( query ) {
        if( typeof query !== 'string' ) {
            return false;
        }

        return query
            .replace( /[|\\{}()[\]^$+*?.]/g, '\\$&' )
            .replace( /-/g, '\\x2d' );
    }

    function getDisplayTitle( prefixedTitle ) {
        var regexpUserNotesTitle = /UserNotes:([^\/]+)\/(.*)/;

        var regexpMatches = prefixedTitle.match( regexpUserNotesTitle );

        if( !regexpMatches ) {
            return false;
        }

        return mw.msg( 'usernotes-articletitle', regexpMatches[ 2 ] );
    }

    function titleWidgetHandler( data ) {
        if( data.action === 'impression-results' ) {
            // We need a tiny delay to change the html of the ooui widget or it will get changed back immediately
            setTimeout( function() {
                var query = data.query;

                $( '.mw-widget-titleWidget-menu > .mw-widget-titleOptionWidget a' ).each( function() {
                    var displayTitle = getDisplayTitle( $( this ).text() );

                    if( displayTitle ) {
                        var highlightRegexp = new RegExp( '(' + escapeQuery(query) + ')', 'i' );

                        displayTitle = displayTitle.replace( highlightRegexp, '<span class="oo-ui-labelElement-label-highlight">$1</span>' );

                        $( this ).html( displayTitle );
                    }
                } );
            }, 1 );
        }
    }

    mw.trackSubscribe( 'mediawiki.searchSuggest', function ( topic, data ) {
        if( data.action === 'impression-results' ) {
            var query = data.query;

            $( '.suggestions-result' ).each( function() {
                var displayTitle = getDisplayTitle( $( this ).text() );

                if( displayTitle ) {
                    var highlightRegexp = new RegExp( '(' + escapeQuery(query) + ')', 'i' );

                    displayTitle = displayTitle.replace( highlightRegexp, '<span class="highlight">$1</span>' );

                    $( this ).html( displayTitle );
                }
            } );
        }
    } );

    mw.trackSubscribe( 'mw.widgets.SearchInputWidget', function ( topic, data ) {
        titleWidgetHandler( data );
    } );

    mw.trackSubscribe( 'mw.widgets.TitleWidget', function ( topic, data ) {
        titleWidgetHandler( data );
    } );

}() );