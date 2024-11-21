<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use Olodoc\DocumentManagerInterface;

/**
 * Multi-byte ucfirst
 */
if (! function_exists('mb_ucfirst') 
    && function_exists('mb_substr')) {
    function mb_ucfirst($string) {
        $string = mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
        return $string;
    }
}
/**
 * @author Oloma <support@oloma.dev>
 *
 * Page Generator
 *
 * It is responsible for management page functions such as doc title, pagination, 
 * menus, js codes and links for links to ".html" pages.
 */
class PageGenerator implements PageGeneratorInterface
{
    /**
     * Current menu data
     * 
     * @var array
     */
    protected $menu = array();

    /**
     * Current page meta data
     * 
     * @var string
     */
    protected $meta = array();

    /**
     * Current html data
     * 
     * @var array
     */
    protected $data = array();

    /**
     * Js class
     * 
     * @var object
     */
    protected $jsGenerator;

    /**
     * MenuGenerator class
     * 
     * @var object
     */
    protected $menuGenerator;

    /**
     * DocumentManager class
     * 
     * @var object
     */
    protected $documentManager;

    /**
     * PaginationGenerator class
     * 
     * @var object
     */
    protected $paginationGenerator;

    /**
     * BreadCrumbGenerator class
     * 
     * @var object
     */
    protected $breadCrumbGenerator;

    /**
     * Constructor
     * 
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->menuGenerator = new MenuGenerator($documentManager);
        $this->data = $this->menuGenerator->generate();
        $this->jsGenerator = new JsGenerator($documentManager);
        $this->paginationGenerator = new PaginationGenerator($documentManager, $this->menuGenerator);
        $this->breadCrumbGenerator = new BreadCrumbGenerator($documentManager, $this->menuGenerator);
    }

    /**
     * Returns to current page meta data
     * 
     * @return object
     */
    public function getMeta() : array
    {
        return $this->meta;
    }

    /**
     * Returns to Menu Generator class
     * 
     * @return object
     */
    public function getMenu() : MenuGeneratorInterface
    {
        return $this->menuGenerator;
    }

    /**
     * Returns to document manager class
     * 
     * @return object
     */
    public function getDocumentManager() : DocumentManagerInterface
    {
        return $this->documentManager;
    }

    /**
     * Returns to breadcrumb generator class
     * 
     * @return object
     */
    public function getBreadCrumb() : BreadCrumbGeneratorInterface
    {
        return $this->breadCrumbGenerator;
    }

    /**
     * Returns to Pagination Generator class
     * 
     * @return object
     */
    public function getPagination() : PaginationGeneratorInterface
    {
        return $this->paginationGenerator;
    }
        
    /**
     * Returns to Js Generator class
     * 
     * @return object
     */
    public function getJs() : JsGeneratorInterface
    {
        return $this->jsGenerator;
    }

    /**
     * Generates page items and returns array data
     * 
     * @return array data
     */
    public function generate()
    {  
        $this->menu = $this->menuGenerator->getMenu();
        $this->setMeta();
        return $this->data;
    }

    /**
     * Sets page meta data
     *
     * @return void
     */
    protected function setMeta()
    {
        $path = $this->documentManager->getRequest()->getUri()->getPath();
        $defaultMeta = array(
            'title' => null,
            'keywords' => null,
            'description' => null,
        );
        $currentPath = str_replace(
            "/".$this->documentManager->getVersion(),  // remove version number
            "",
            $path
        );
        $item = array_filter(
            $this->menu, 
            function ($v) use($currentPath) {
                if (! empty($v['url'])) {
                    return strpos("/".ltrim($v['url'], "/"), $currentPath) !== false;    
                }
                return false;
            }
        );
        $this->meta = empty($item['meta']) ? $defaultMeta : $item['meta'];
    }

    /**
     * Returns to html version combobox
     * 
     * @param  string $versionText version text
     * @return string
     */
    public function getVersionCombobox(string $versionText = "Version") : string
    {
        $html = "<select id=\"version-combobox\" class=\"form-select\" onchange=\"olodocChangeVersion()\">".PHP_EOL;
            foreach ($this->documentManager->getAvailableVersions() as $version) {
                $selected = ($this->documentManager->getVersion() == $version) ? "selected" : "";
                $html.= "<option value=\"$version\" $selected>".$versionText." $version</option>".PHP_EOL;  
            }
        $html.= "</select>";
        return $html.PHP_EOL;
    }

    /**
     * Returns to search box input
     * 
     * @return string
     */
    public function getSearchBoxInput() : string
    {
        return '<div id="search-box" class="input-box">
              <input type="text" id="search-input" class="form-control input-lg" onkeyup="olodocSearchResult(this.value)" />
              <svg id="cancel-icon" style="display:none;cursor:pointer;" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -860 960 960" width="24px" fill="currentColor"><path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/></svg>
              <svg id="search-icon" xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 -860 960 960" fill="currentColor"><path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/></svg>
            </div>'.PHP_EOL;
    }

    /**
     * Returns to navigation bar html
     * 
     * @return string
     */
    public function getFooter($prevPageLabel = "", $nextPageLabel = "") : string
    {
        return $this->paginationGenerator->getPaginationBar(
            $prevPageLabel,
            $nextPageLabel
        );
    }

}