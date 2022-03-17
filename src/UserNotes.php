<?php

namespace UserNotes;

use BootstrapUI\BootstrapUI;
use Html;
use MediaWiki\MediaWikiServices;
use OutputPage;
use RequestContext;
use Title;
use User;

class UserNotes {

    protected const EXTENSION_NAME = 'UserNotes';

    public static function canTitleHaveUserNotesArticle( $title ) {
        global $wgUserNotesBlacklistTitles, $wgUserNotesEnabledNamespaces;

        if( !$title ) {
            return false;
        }

        if( !$title instanceof Title ) {
            $title = Title::newFromText( $title );
        }

        $titleNamespaceText = $title->getNsText() ? $title->getNsText() : 'Main';

        if( !in_array( $titleNamespaceText, $wgUserNotesEnabledNamespaces )
            || in_array( $title->getDBkey(), $wgUserNotesBlacklistTitles ) ) {
            return false;
        }

        return true;
    }

    public static function getExtensionName(): string {
        return self::EXTENSION_NAME;
    }

    /**
     * @param Title|string $userNotesTitle
     * @return null|Title
     */
    public static function getMainArticleTitle( $userNotesTitle ) {
        if( !$userNotesTitle ) {
            return null;
        }

        if( !$userNotesTitle instanceof Title ) {
            $userNotesTitle = Title::newFromText( $userNotesTitle );
        }

        return Title::newFromText(
            preg_replace( '/' . preg_quote( $userNotesTitle->getRootText() ) . '\/?/', '', $userNotesTitle->getText() )
        );
    }

    public static function getUserFromUserNotesTitle( $userNotesTitle ) {
        if( !$userNotesTitle ) {
            return false;
        }

        if( !$userNotesTitle instanceof Title ) {
            $userNotesTitle = Title::newFromText( $userNotesTitle );
        }

        if( !$userNotesTitle || $userNotesTitle->getNamespace() != NS_USERNOTES ) {
            return false;
        }

        return User::newFromName( $userNotesTitle->getRootText() );
    }

    public static function getUserNotesArticleDisplayTitle( $titleText ): string {
        if( !$titleText ) {
            return '';
        }

        # If prefixed text is provided, parse out the title
        $titleText = preg_replace( '/UserNotes:(?<username>[^\/]+)\/(?<title>.*)/', '$2', $titleText );

        return wfMessage( 'usernotes-articletitle', $titleText )->text();
    }
    
    public static function init() {
        global $wgNonincludableNamespaces;

        $wgNonincludableNamespaces[] = NS_USERNOTES;
    }

    public static function isTitleUserNotesArticle( $title ) {
        if( !$title ) {
            return false;
        }

        if( !$title instanceof Title ) {
            $title = Title::newFromText( $title );
        }

        if( $title->getNamespace() != NS_USERNOTES ) {
            return false;
        }

        return true;
    }

    /**
     * @param string $search
     * @return false|Title[]
     */
    public static function searchUserNotesArticleTitles( string $search ) {
        if( strpos( $search, ':' ) !== false ) {
            return false;
        }

        $myUser = RequestContext::getMain()->getUser();

        if( !$myUser->isRegistered() ) {
            return false;
        }

        $searchEngine = MediaWikiServices::getInstance()->newSearchEngine();

        return $searchEngine->extractTitles( $searchEngine->completionSearchWithVariants( 'UserNotes:' . $myUser->getName() . '/' . $search ) );
    }

    public static function setTabs() {
        global $wgUserNotesOtherNotesNamespaces;

        $user = RequestContext::getMain()->getUser();
        $title = RequestContext::getMain()->getTitle();

        $mainArticleTitle = $title;

        if( $title->isTalkPage() ) {
            $mainArticleTitle = $title->getOtherPage();
        }

        if( self::isTitleUserNotesArticle( $title )
            || in_array( $mainArticleTitle->getNsText(), $wgUserNotesOtherNotesNamespaces ) ) {
            $mainArticleTitle = self::getMainArticleTitle( $mainArticleTitle );
        }

        if( !$user->isRegistered()
            || !self::canTitleHaveUserNotesArticle( $mainArticleTitle ) ) {
            return;
        }

        $navManager = BootstrapUI::getNavManager();

        $navId = 'personalnotes';

        $userNotesTitle = self::isTitleUserNotesArticle( $title ) ? $title :
            Title::newFromText( 'UserNotes:' . $user->getName() . '/' . $mainArticleTitle->getText() );

        $userNotesLinkMessage = $userNotesTitle->exists() ? 'usernotes-personalnotes' : 'usernotes-personalnotesnew';

        $tabContents = BootstrapUI::iconWidget( [ 'class' => 'fas fa-clipboard fa-fw' ] ) .
            Html::rawElement( 'span', [
                'class' => 'nav-label'
            ], wfMessage( $userNotesLinkMessage )->text() );

        $navManager->addNavItem( $navId, [
            'active' => self::isTitleUserNotesArticle( $title ),
            'href' => $userNotesTitle->getLocalURL()
        ], $tabContents );

        $navManager->positionNavItem( 'discussion', 'last' );
        $navManager->positionNavItem( 'menu', 'last' );

        # Modify article tab
        $navId = 'article';

        $navItem = $navManager->getNavItem( $navId );

        if( self::isTitleUserNotesArticle( $title ) ) {
            $navItem[ 'active' ] = false;
            $navItem[ 'href' ] = $mainArticleTitle->getLocalURL();
        }

        $articleTabMessage = $mainArticleTitle->exists() ? 'usernotes-mainarticle' : 'usernotes-mainarticlenew';

        $navItem[ 'contents' ] = BootstrapUI::iconWidget( [ 'class' => 'fas fa-file-alt' ] ) .
            Html::rawElement( 'span', [
                'class' => 'nav-label'
            ], wfMessage( $articleTabMessage )->text() );

        $navManager->addNavItem( $navId, $navItem );

        # If title is a usernotes article, modify the article and discussion tabs
        if( self::isTitleUserNotesArticle( $title ) ) {
            $navId = 'discussion';

            $navItem = $navManager->getNavItem( $navId );
            $navItem[ 'href' ] = $mainArticleTitle->getTalkPageIfDefined()->getLocalURL();

            $navManager->addNavItem( $navId, $navItem );
        }
    }

    /**
     * This function returns whether a user should be able to read a usernotes title.
     * If the title passed is not a usernotes title, it will return false.
     * @param Title $title
     * @return bool
     */
    public static function userCanReadUserNotesTitle( Title $title, $user = null ): bool {
        $userNotesUser = static::getUserFromUserNotesTitle( $title );

        if( !$userNotesUser ) {
            return false;
        }

        $user = $user ?? RequestContext::getMain()->getUser();

        if( !$user->getId() || $user->getId() != $userNotesUser->getId() ) {
            return false;
        }

        return true;
    }

    public static function wrapRenderShield( OutputPage &$out ) {
        $out->prependHTML(
            Html::openElement( 'div', [
                'class' => 'usernotes-rendershield',
                'style' => 'visibility: hidden;'
            ] )
        );

        $out->addHTML(
            Html::closeElement( 'div' )
        );
    }
}