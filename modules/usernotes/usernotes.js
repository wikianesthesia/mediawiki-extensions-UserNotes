/**
 * @author Chris Rishel
 */
( function () {

    if( typeof mw === 'undefined' || mw === null ) {
        throw "";
    }

    mw.userNotes = {
        createDialogExistingUserNotesTitles: [],
        createDialogExistingPublicTitles: [],
        addHandlers: function() {
            $( '#usernotes-createarticle-button' ).click( function() {
                mw.userNotes.createArticleClick();
            } );
        },
        createArticleClick: function() {

            var existingPublicResultsDefault = $( '<i>' ).append( mw.msg( 'usernotes-create-dialog-existingpublicarticles-noresults' ) );
            var existingUserNotesResultsDefault = $( '<i>' ).append( mw.msg( 'usernotes-create-dialog-existingusernotesarticles-noresults' ) );

            var modalContent = $( '<div>' ).append( $( '<div>', {
                'class': 'form-group'
            } ).append( $( '<label>', {
                'for': '#modalCreateTitle'
            } ).append( mw.msg( 'usernotes-create-dialog-label' )
            ), $( '<input>', {
                'type': 'text',
                'class': 'form-control',
                'id': 'modalCreateTitle',
                'aria-describedby': 'modalCreateTitleHelp',
                'autocomplete': 'off'
            } ), $( '<small>', {
                'id': 'modalCreateTitleHelp',
                'class': 'form-text text-muted'
            } ).append( mw.msg( 'usernotes-create-dialog-help' )
            ) ), $( '<div>', {
                'id': 'modalCreateExistingPublic'
            } ).append( mw.msg( 'usernotes-create-dialog-existingpublicarticles'
            ), $( '<div>', {
                'id': 'modalCreateExistingPublicResults',
                'class': 'm-3'
            } ).append( existingPublicResultsDefault
            ) ), $( '<div>', {
                'id': 'modalCreateExistingUserNotes'
            } ).append( mw.msg( 'usernotes-create-dialog-existingusernotesarticles'
            ), $( '<div>', {
                'id': 'modalCreateExistingUserNotesResults',
                'class': 'm-3'
            } ).append( existingUserNotesResultsDefault
            ) ) );

            $( '#modalCreate' ).remove();

            $( '#bodyContent' ).prepend( $( '<div>', {
                'class': 'modal fade',
                'id': 'modalCreateArticle',
                'tabindex': '-1',
                'role': 'dialog',
                'aria-labelledby': 'modalCreateLabel',
                'aria-hidden': 'true'
            } ).append( $( '<div>', {
                'class': 'modal-dialog',
                'role': 'document'
            } ).append( $( '<div>', {
                'class': 'modal-content'
            } ).append( $( '<div>', {
                    'class': 'modal-header'
                } ).append( $( '<h5>', {
                    'class': 'modal-title pt-0',
                    'id': 'modalConfirmLabel'
                } ).append( mw.msg( 'usernotes-create-dialog-header' )
                ), $( '<button>', {
                    'type': 'button',
                    'class': 'close',
                    'data-dismiss': 'modal',
                    'aria-label': 'Close'
                } ).append( $( '<span>', {
                    'aria-hidden': true
                } ).append( '&times;'
                ) ) ), $( '<div>', {
                    'class': 'modal-body'
                } ).append( modalContent ),
                $( '<div>', {
                    'class': 'modal-footer'
                } ).append( $( '<button>', {
                        'type': 'button',
                        'class': 'btn btn-primary',
                        'data-dismiss': 'modal',
                        'disabled': true,
                        'id': 'modalCreateProceed'
                    } ).append( mw.msg( 'usernotes-create-dialog-proceedbutton' ) ),
                    $( '<button>', {
                        'type': 'button',
                        'class': 'btn btn-secondary',
                        'data-dismiss': 'modal'
                    } ).append( mw.msg( 'usernotes-cancel' ) )
                )))));

            $( '#modalCreateProceed' ).click( function() {
                $( '#modalCreateTitle' ).val( $( '#modalCreateTitle' ).val().trim() );

                window.location.href = mw.Title.newFromText(
                    'UserNotes:' + mw.user.getName() + '/' + $( '#modalCreateTitle' ).val()
                ).getUrl() + '?veaction=edit';
            } );

            $( '#modalCreateTitle' ).on( 'input', function() {
                if( $( this ).val() ) {
                    $( this ).val( $( this ).val().charAt( 0 ).toUpperCase() + $( this ).val().slice( 1 ) );

                    $( '#modalCreateProceed' ).prop( 'disabled', false );

                    var maxRows = 5;

                    var api = new mw.Api();

                    api.get( {
                        'action': 'query',
                        'list': 'search',
                        'srsearch': 'intitle:' + $( '#modalCreateTitle').val(),
                        'srnamespace': '0',
                        'srprop': '',
                        'srlimit': maxRows
                    } ).done( function ( apiResult ) {
                        mw.userNotes.createDialogExistingUserNotesTitles = [];
                        mw.userNotes.createDialogExistingPublicTitles = [];

                        var iResult = 0;

                        var existingPublicTitleList = $( '<div>', {
                            'class': 'usernotes-createarticle-searchresults'
                        } );

                        while( mw.userNotes.createDialogExistingPublicTitles.length < maxRows && iResult < apiResult.query.search.length ) {
                            if( apiResult.query.search[ iResult ].hasOwnProperty( 'title' ) ) {
                                mw.userNotes.createDialogExistingPublicTitles.push( apiResult.query.search[ iResult ].title );

                                existingPublicTitleList.append(
                                    $( '<a>', {
                                        'href': mw.Title.newFromText(
                                            'UserNotes:' + mw.user.getName() + '/' + apiResult.query.search[ iResult ].title
                                        ).getUrl() + '?veaction=edit'
                                    } ).append( apiResult.query.search[ iResult ].title ), '<br/>'
                                );
                            }

                            iResult++;
                        }

                        var existingUserNotesTitleList = $( '<div>', {
                            'class': 'usernotes-createarticle-searchresults'
                        } );

                        var tableArticlesData = $( '#table-usernotes' ).DataTable().data().toArray();

                        for( var i in tableArticlesData ) {
                            var existingArticleTitle = tableArticlesData[ i ][ 0 ].replace( /(<([^>]+)>)/gi , "");
                            var existingArticleTitleWords = existingArticleTitle.toLowerCase().split( ' ' );

                            var newTitleWords = $( '#modalCreateTitle').val().toLowerCase().split( ' ' );

                            var titleMatch = false;

                            for( var iExistingArticleTitleWord in existingArticleTitleWords ) {
                                for( var iNewTitleWord in newTitleWords ) {
                                    if( existingArticleTitleWords[ iExistingArticleTitleWord ] === newTitleWords[ iNewTitleWord ] ) {
                                        titleMatch = true;

                                        break;
                                    }
                                }
                            }

                            if( titleMatch ) {
                                mw.userNotes.createDialogExistingUserNotesTitles.push( existingArticleTitle );

                                existingUserNotesTitleList.append( existingArticleTitle, '<br/>' );
                            }
                        }

                        $( '#modalCreateExistingPublicResults' ).html( mw.userNotes.createDialogExistingPublicTitles.length
                            ? existingPublicTitleList
                            : existingPublicResultsDefault
                        );

                        $( '#modalCreateExistingUserNotesResults' ).html( mw.userNotes.createDialogExistingUserNotesTitles.length
                            ? existingUserNotesTitleList
                            :existingUserNotesResultsDefault
                        );
                    } );
                } else {
                    $( '#modalCreateExistingPublicResults' ).html( existingPublicResultsDefault );
                    $( '#modalCreateExistingUserNotesResults' ).html( existingUserNotesResultsDefault );

                    $( '#modalCreateProceed' ).prop( 'disabled', true );
                }
            } ).on( 'keyup', function( e ) {
                if( e.key === 'Enter' ) {
                    $( '#modalCreateProceed' ).trigger( 'click' );
                }
            } );

            if( $( '#usernotes-articles-search' ).val() ) {
                $( '#modalCreateTitle' ).val( $( '#usernotes-articles-search' ).val() );
                $( '#modalCreateTitle' ).trigger( 'input' );
            }

            $( '#modalCreateArticle' )
                .on( 'shown.bs.modal', function() {
                    $( '#modalCreateTitle' ).focus();
                } )
                .modal( 'show' );
        },
        init: function() {
            mw.userNotes.renderDataTables();
            mw.userNotes.addHandlers();

            $( '.usernotes-rendershield' ).contents().unwrap();
        },
        renderDataTables: function() {
            if( $( '#table-usernotes' ).length ) {
                $( '#table-usernotes' ).DataTable( {
                    'lengthChange': false,
                    'ordering': false,
                    'pageLength': 25,
                    'initComplete': function() {
                        $( '#table-usernotes_filter' ).parent().parent().children().first().append( $( '#table-usernotes_filter' ).css( 'float', 'left' ) );
                    }
                } );
            }
        }
    };

    mw.userNotes.init();

}() );