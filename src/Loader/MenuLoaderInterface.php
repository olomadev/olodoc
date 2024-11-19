<?php

namespace Olodoc\Loader;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Menu Loader Interface
 */
interface MenuLoaderInterface
{
    /**
     * Load documentation menu array
     * 
     * @param  string $directory optional
     * @return array
     */
    public function loadMenu(string $directory = "") : array;

    /**
     * Current (dynamic) menu data
     * 
     * @return array
     */
    public function getMenuArray() : array;

    /**
     * Returns to menu conguration array
     * 
     * @return array
     */
    public function getMenuConfig() : array;
}