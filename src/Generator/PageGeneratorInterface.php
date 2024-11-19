<?php

namespace Olodoc\Generator;

use Olodoc\DocumentManagerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Html Page Generator Interface
 */
interface PageGeneratorInterface
{    
    /**
     * Returns to Menu Generator class
     * 
     * @return object
     */
    public function getMenu() : MenuGeneratorInterface;
        
    /**
     * Returns to breadcrumb generator class
     * 
     * @return object
     */
    public function getBreadCrumb() : BreadCrumbGeneratorInterface;

    /**
     * Returns to js generator class
     * 
     * @return object
     */
    public function getJs() : JsGeneratorInterface;

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
     * Returns to navigation bar html
     * 
     * @return string
     */
    public function getFooter() : string;

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
}