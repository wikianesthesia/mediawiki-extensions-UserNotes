( function () {
    function escapeQuery( query ) {
        if( typeof query !== 'string' ) {
            return false;
        }

        return query
            .replace( /[|\\{}()[\]^$+*?.]/g, '\\$&' )
            .replace( /-/g, '\\x2d' );
    }

    function getUserNotesTitleHtml( prefixedTitle, query, highlightClass ) {
        var regexpUserNotesTitle = /UserNotes:([^\/]+)\/(.*)/;

        var regexpMatches = prefixedTitle.match( regexpUserNotesTitle );

        if( !regexpMatches ) {
            return false;
        }

        var displayTitle = regexpMatches[ 2 ];

        var highlightRegexp = new RegExp( '(' + escapeQuery(query) + ')', 'i' );
        displayTitle = displayTitle.replace( highlightRegexp, '<span class="' + highlightClass + '">$1</span>' );

        var badgeAttribs = {
            'class': 'badge usernotes-searchresults-badge'
        };

        var $userNotesBadge = $( '<h6>', {} ).append(
            $( '<span>', badgeAttribs ).append( mw.msg( 'usernotes-personalnotes' ) )
        );

        return $userNotesBadge[0].innerHTML + displayTitle;
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
                    $( '.mw-widget-titleWidget-menu > .mw-widget-titleOptionWidget a' ).each( function() {
                        var userNotesTitleHtml = getUserNotesTitleHtml( $( this ).text(), query, 'oo-ui-labelElement-label-highlight' );

                        if( userNotesTitleHtml ) {
                            $( this ).html( userNotesTitleHtml );
                        }
                    } );
                } );
            }, 1 );
        }
    }

    mw.trackSubscribe( 'mediawiki.searchSuggest', function ( topic, data ) {
        if( data.action === 'impression-results' ) {
            var query = data.query;

            $( '.suggestions-result' ).each( function() {
                var userNotesTitleHtml = getUserNotesTitleHtml( $( this ).text(), query, 'highlight' );

                if( userNotesTitleHtml ) {
                    $( this ).html( userNotesTitleHtml );
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