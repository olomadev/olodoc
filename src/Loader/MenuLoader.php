<?php

declare(strict_types=1);

namespace Olodoc\Loader;

use Olodoc\DocumentManagerInterface;
use Olodoc\Exception\FileNotFoundException;
use Olodoc\Exception\ConfigurationErrorException;

/**
 * Check is array is associative
 */
if (! function_exists('array_is_list')) {
    function array_is_list(array $arr) {
        if ($arr === []) {
            return true;
        }
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
/**
 * @author Oloma <support@oloma.dev>
 *
 * Menu Loader
 *
 * Responsible for loading menus
 */
class MenuLoader implements MenuLoaderInterface
{
    /**
     * Current direcytory path e.g. (ui, ui/layouts)
     * 
     * @var string
     */
    protected $directory;
    
    /**
     * Current (dynamic) menu data
     * 
     * @var array
     */
    protected $menuArray = array();

    /**
     * Menu configuration array
     * 
     * @var array
     */
    protected $menuConfig = array();

    /**
     * Menus with directory names as keys
     * 
     * @var array
     */
    protected $folderableMenu = array();

    /**
     * Document manager class
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
        $this->directory = $directory;
        $file = $this->documentManager->getMenuFile();
        $translator = $this->documentManager->getTranslator();
        if (! is_file($file)) {
            throw new FileNotFoundException(
                "Your navigation.php file does't exists in your config folder."
            );
        }
        $this->menuArray = $this->menuConfig = require $file;
        if (! is_array($this->menuConfig)) {
            throw new ConfigurationErrorException(
                "Navigation items must be array."
            );
        }
        if (! array_is_list($this->menuConfig)) {
            throw new ConfigurationErrorException(
                "Navigation items must not be an associative array it must be simple array list."
            );
        }
        $this->menuArray = $this->buildMenuAndFolders($this->menuArray);
        if (! empty($directory) && 
            ! empty($this->folderableMenu[$directory])) 
        {
            $this->menuArray = $this->folderableMenu[$directory];
        }
        return $this->menuArray;
    }

    /**
     * Current (dynamic) menu data
     * 
     * @return array
     */
    public function getMenuArray() : array
    {
        return $this->menuArray;
    }

    /**
     * Returns to menu conguration array
     * 
     * @return array
     */
    public function getMenuConfig() : array
    {
        return $this->menuConfig;
    }

    /**
     * Build recursive menu and folderable menu array
     * 
     * @param  array  $menu array
     * @return array
     */
    protected function buildMenuAndFolders(array $menu) : array
    {
        foreach ($menu as $val) {
            /**
             * If the first child metadata is empty and the child and parent url values 
             * are equal to index.html, copy the first child metadata to the parent directory 
             * folder configuration for maximum compatibility.
             */
            if (! empty($val['folder']) 
                && ! empty($val['meta']) 
                && ! empty($val['children'])
                && ! empty($val['url'])
                && empty($val['children'][0]['meta'])
                && false !== strpos($val['url'], $this->documentManager::INDEX_PAGE)
                && ! empty($val['children'][0]['url'])
                && false !== strpos($val['children'][0]['url'], $this->documentManager::INDEX_PAGE)
            ) {
                $val['children'][0]['meta'] = $val['meta'];
            }
            if (! empty($val['children'])) {
                $folder = rtrim(ltrim($val['folder'], "/"), "/");
                $folderKey = mb_strtolower($folder);
                $this->folderableMenu[$folderKey] = $val['children'];
                $newMenuArray = $val['children'];
                $this->buildMenuAndFolders($newMenuArray);
            }
        }
        return $menu;
    }

}
