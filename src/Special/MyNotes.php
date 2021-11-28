<?php

namespace UserNotes\Special;

use BootstrapUI\BootstrapUI;
use Html;
use MediaWiki\MediaWikiServices;
use Title;
use SpecialPage;
use UserNotes\UserNotes;

/*
 * This special page will generate a table of all usernotes subpages for a user with controls to edit, move, and delete
 * TODO If userCan(usernotes-viewall), show a search box with autocomplete to search for other users' usernotes pages.
 * Controls depend on userCan() validation for the relevant action.
 * Orphaned pages need options to relink and delete
 */

class MyNotes extends SpecialPage {
    public function __construct() {
        parent::__construct( 'MyNotes' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $this->outputHeader();

        $user = $this->getUser();

        if( !$user->isLoggedIn() ) {
            return;
        }

        $out = $this->getOutput();
        $out->addModules( 'ext.userNotes' );

        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        $myNotesTitle = Title::newFromText( 'UserNotes:' . $user->getName() );

        $articles = $myNotesTitle->getSubpages();

        $myNotesHtml = '';

        $myNotesHtml .= Html::openElement( 'div', [
            'class' => 'mt-3 mb-3'
        ] );

        $myNotesHtml .= BootstrapUI::buttonWidget( [
            'id' => 'usernotes-createarticle-button',
            'icon' => 'fas fa-plus',
            'label' => wfMessage( 'usernotes-create-article-button' )->text()
        ] );

        $myNotesHtml .= Html::closeElement( 'div' );

        if( count( $articles ) ) {
            $myNotesHtml .= Html::openElement( 'div', [
                'class' => 'mt-3'
            ] );

            $myNotesHtml .= Html::openElement( 'table', [
                'class' => 'table table-sm',
                'id' => 'table-usernotes'
            ] );

            $thAttribs = [
                'scope' => 'col'
            ];

            $tdAttribs = [
                'class' => 'align-middle'
            ];

            $buttonAttribs = [
                'class' => 'align-middle usernotes-buttons',
                'style' => 'text-align: right'
            ];

            $myNotesHtml .= Html::openElement( 'thead' );

            $myNotesHtml .= Html::rawElement( 'tr', [],
                Html::rawElement('th', $thAttribs, wfMessage( 'usernotes-personalnotes' )->text() )
                // . Html::rawElement('th', '' )
            );

            $myNotesHtml .= Html::closeElement( 'thead' );

            $myNotesHtml .= Html::openElement( 'tbody' );

            foreach( $articles as $article ) {
                $mainArticleTitle = UserNotes::getMainArticleTitle( $article );

                $myNotesHtml .= Html::openElement( 'tr' );

                $myNotesHtml .= Html::rawElement( 'td', $tdAttribs,
                    $linkRenderer->makeLink( $article, $mainArticleTitle->getText() )
                );
/*
                $myNotesHtml .= Html::rawElement( 'td', $buttonAttribs,
                    BootstrapUI::buttonWidget( [
                        'buttonStyle' => BootstrapUI::BUTTON_STYLE_OUTLINE_SECONDARY,
                        'class' => 'bs-ui-buttonHideLabelMobile',
                        'href' => $mainArticleTitle->getLinkURL(),
                        'icon' => BootstrapUI::iconWidget( [ 'class' => 'fas fa-link' ] ),
                        'label' => wfMessage( 'usernotes-viewpublicarticle' )->text()
                    ] )
                );
*/
                $myNotesHtml .= Html::closeElement( 'tr' );
            }

            $myNotesHtml .= Html::closeElement( 'tbody' );

            $myNotesHtml .= Html::closeElement( 'table' );
            $myNotesHtml .= Html::closeElement( 'div' );
        }

        $out->addHTML( $myNotesHtml );

        UserNotes::wrapRenderShield( $out );
    }
}