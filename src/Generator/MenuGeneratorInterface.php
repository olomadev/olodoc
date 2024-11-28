<?php

namespace Olodoc\Generator;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Menu Generator Interface
 */
interface MenuGeneratorInterface
{
    /**
     * Generates sub menus using directory and page inputs
     * 
     * @return array
     */
    public function generate() : array;

    /**
     * Returns to menu array
     * 
     * @return array
     */
    public function getMenu() : array;

    /**
     * Returns to curremt page label
     * 
     * @return string
     */
    public function getPageLabel() : string;
    
    /**
     * Returns sidebar links
     *
     * @return string
     */
    public function getSideNavbarLinks();

    /**
     * Returns to segments
     * 
     * @return array
     */
    public function getSegments() : array;

    /**
     * Returns to sidebar header links
     * 
     * @param  string $indexText      index translation
     * @param  string $backToMenuText back to menu link translation
     * @return string
     */
    public function getSidebarHeader($indexText = "Index", $backToMenuText = "Back to Menu") : string;

    /**
     * Returns to go back link
     *
     * @return string
     */
    public function getGoToBackLink() : string;

}