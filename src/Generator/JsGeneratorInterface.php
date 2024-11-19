<?php

namespace Olodoc\Generator;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Js Generator Interface
 */
interface JsGeneratorInterface
{
    /**
     * Generates all js codes
     * 
     * @return string
     */
    public function generate();
    
    /**
     * Returns to page navigation javascript
     * 
     * @return string
     */
    public function getPaginationJs() : string;

    /**
     * Returns to search icon javascript
     * 
     * @return string
     */
    public function getSearchIconsJs() : string;

    /**
     * Returns to no search result javascript
     * 
     * @return string
     */
    public function getSearchNoResultsJs() : string;

    /**
     * Returns to search results javascript
     * 
     * @return string
     */
    public function getSearchResultsJs() : string;

    /**
     * Returns to bootstrap tab init script for prism js
     * 
     * @return string
     */
    public function getBoostrapTabPrismJs() : string;

    /**
     * Returns to language mouseover javascript
     *
     * @return string
     */
    public function getLanguagesFlagJs();

}