<?php

namespace Olodoc\Search;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Document Search Interface
 */
interface DocumentSearchInterface
{
    /**
     * Set hlite open tag
     * 
     * @param string $tagOpen html tag
     */
    public function setHliteOpenTag(string $tagOpen);

    /**
     * Set hlite close tag
     * 
     * @param string $tagOpen html tag
     */
    public function setHliteCloseTag(string $tagClose);

    /**
     * Returns to hlite open tag
     * 
     * @return string
     */
    public function getHliteOpenTag() : string;

    /**
     * Returns to hlite close tag
     * 
     * @return string
     */
    public function getHliteCloseTag() : string;
    
    /**
     * Returns to search keywords
     * 
     * @return string
     */
    public function getQueryString();
    
    /**
     * Start searching static files by reading them line by line
     * 
     * @param  string $query words
     * @return array
     */
    public function search(string $query) : array;

}