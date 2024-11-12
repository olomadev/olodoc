<?php

namespace Olodoc\Generator;

use Olodoc\DocumentManagerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Html Generator Interface
 */
interface PageGeneratorInterface
{    
    /**
     * Returns to document manager class
     * 
     * @return object
     */
    public function getDocumentManager() : DocumentManagerInterface;

    /**
     * Generate page data
     * 
     * @return array data
     */
    public function generate();

    /**
     * Returns to translated sub menu title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Returns to translated child menu title
     *
     * @return string
     */
    public function getSubTitle();

    /**
     * Returns to menu text
     * 
     * @param  string $backToMenuText text
     * @return string
     */
    public function getBackToMenuLink($backToMenuText = 'Back to Menu') : string;

    /**
     * Returns to navigation bar html
     * 
     * @return string
     */
    public function getPageFooter() : string;

    /**
     * Returns to javascript codes
     * 
     * @return string
     */
    public function getJavascript() : string;

    /**
     * Returns to current page links
     *
     * @return string
     */
    public function getCurrentPageLinks();

    /**
     * Returns to child page links
     *
     * @return string
     */
    public function getChildPageLinks();

    /**
     * Returns to html version combobox
     * 
     * @param  string $versionText version text
     * @return string
     */
    public function getVersionCombobox(string $versionText = "Version") : string;

   /**
     * Returns to search box input
     * 
     * @return string
     */
    public function getSearchBoxInput() : string;

    /**
     * Returns to bread crumbs
     * 
     * @return string
     */
    public function getBreadCrumbs(string $indexName = "Index") : string;
    
}