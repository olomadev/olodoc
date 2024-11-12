<?php

declare(strict_types=1);

namespace Olodoc\Loader;

use Olodoc\DocumentManagerInterface;
use Olodoc\Exception\FileNotFoundException;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Menu Loader
 *
 * Responsible for loading menus
 */
class MenuLoader
{
    /**
     * Menu data
     * 
     * @var array
     */
    protected $menuArray = array();

    /**
     * Folderable menu data
     * 
     * @var array
     */
    protected $folderMenuArray = array();

    /**
     * Document manager
     * 
     * @var object
     */
    protected $documentManager;

    /**
     * Constructor
     * 
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * Load documentation menu array
     * 
     * @param  string $directory optional
     * @return array
     */
    public function loadMenu(string $directory = "") : array
    {
        //---------------------------------------------------------------------------
        
        $file = $this->documentManager->getMenuFile();
        if (! is_file($file)) {
            throw new FileNotFoundException("Menu configuration file doest not exists in your config folder.");
        }
        $this->menuArray = require $file;
        $this->folderMenuArray = Self::findFolders($this->menuArray);
        if (empty($this->folderMenuArray[$directory]['children'])) {
            $menu = $this->menuArray;
        } else {
            $menu = $this->folderMenuArray[$directory]['children'];
        }
        return $menu;
    }

    /**
     * Returns to menu array
     * 
     * @return array
     */
    public function getMenuArray() : array
    {
        return $this->menuArray;
    }

    /**
     * Returns to folderable menu array
     * 
     * @return array
     */
    public function getFolderableMenuArray() : array
    {
        return $this->folderMenuArray;
    }

    /**
     * Find folders
     * 
     * @param  array  $menuArray menu array
     * @return array
     */
    protected static function findFolders(array $menuArray) : array
    {
        $folderMenuArray = array();
        foreach ($menuArray as $key => $val) {
            if (! empty($val['folder'])) {
                $folderMenuArray[$val['folder']] = $val;
            }
        }
        return $folderMenuArray;
    }

}
