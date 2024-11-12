<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use Olodoc\Loader\MenuLoader;
use Olodoc\Loader\FileLoader;
use Olodoc\DocumentManagerInterface;
use Olodoc\Generator\AnchorGenerator;
use Olodoc\Exception\ConfigurationErrorException;

/**
 * Multi-byte ucfirst
 */
if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {
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
 * It is responsible for creating html page functions such as doc title, pagination, 
 * menus and links for links to ".html" pages.
 */
class PageGenerator implements PageGeneratorInterface
{
    const GOOGLE_FOLDER_ICON = '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="currentColor"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640v400q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H447l-80-80H160v480Zm0 0v-480 480Z"/></svg>';

    protected $title;
    protected $subTitle;
    protected $request;
    protected $baseUrl;
    protected $version;
    protected $menu = array();
    protected $menuArray = array();
    protected $data = array();
    protected $childMenu = array();
    protected $menuLoader;
    protected $fileLoader;
    protected $currentPageLinks;
    protected $childPageLinks;
    protected $documentManager;
    protected $anchorGenerator;

    /**
     * Constructor
     * 
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->menuLoader = new MenuLoader($documentManager);
        $this->fileLoader = new FileLoader($documentManager);
        $this->anchorGenerator = new AnchorGenerator($documentManager);

        $this->request = $documentManager->getRequest();
        $this->baseUrl = $documentManager->getBaseUrl();
        $this->version = $documentManager->getVersion();
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
     * Generate page data
     * 
     * @return array data
     */
    public function generate()
    {   
        //---------------------------------------------------------------------------

        $path = $this->documentManager->getFilePath();
        $directory = $this->documentManager->getDirectory();
        $subDirectory = $this->documentManager->getSubDirectory();
        $subPage = $this->documentManager->getSubPage();
        $page = $this->documentManager->getPage();

        $htmlContent = $this->fileLoader->loadFile($path);
        $this->menu = $this->menuLoader->loadMenu($directory);
        $this->menuArray = $this->menuLoader->getMenuArray();
        $this->data = $this->anchorGenerator->parse($htmlContent);
        $this->data['html'] = '<div id="markdown-content">'.PHP_EOL.$this->data['html'].'</div>'.PHP_EOL;

        //---------------------------------------------------------------------------

        $children = $this->generateMenu($directory, $page);
        $this->generateChildMenu($children, $subDirectory, $subPage);

        return $this->data;
    }

    /**
     * Returns to translated sub menu title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns to translated child menu title
     *
     * @return string
     */
    public function getSubTitle()
    {
        return $this->subTitle;
    }

    /**
     * Returns to menu text
     * 
     * @param  string $backToMenuText text
     * @return string
     */
    public function getBackToMenuLink($backToMenuText = 'Back to Menu') : string
    {
        $directory = $this->documentManager->getDirectory();
        $link= "<div class=\"back-to-menu-link\">";
        $indexHtml = "index.html";
        if ($this->documentManager->getRouteName() == $this->documentManager::SUB_DIRECTORY_ROUTE) {
            $link.= "<a href=\"$this->baseUrl$this->version/$directory/$indexHtml\">«&nbsp;$backToMenuText</a>";
        }
        $link.= "</div>";
        return $link;
    }

    /**
     * Returns to current page data
     * 
     * @return array
     */
    private function getCurrentPageData() : array
    {
        $currentPath = str_replace(
            $this->documentManager::LATEST_VERSION_NAME, // support for latest version
            $this->version,
            $this->request->getUri()->getPath()
        );
        $currentRouteName = $this->documentManager->getRouteName();
        $defaultIndexRoute = $this->documentManager::INDEX_DEFAULT_INDEX;
        $path = strstr($currentPath, "/".$this->version."/");
        if (is_string($path)) {
            $path = str_replace(
                $this->documentManager->getVersion(), 
                "",
                $path
            );
        }
        if (! empty($path)) {
            $path = substr($path, 1);
        }
        $i = 0;
        $currentPageData = array();
        foreach ($this->menu as $val) {
            if ($currentRouteName == $defaultIndexRoute) { // 'doc_default_index.html'
                $currentPageData = $this->menu[$i];
                $currentPageData['current_index'] = $i;
            } else if (is_string($path) && strpos($val['url'], $path) !== false) {
                $currentPageData = $this->menu[$i];
                $currentPageData['current_index'] = $i;
            }
            if (! empty($val['children'])) {
                $c = 0;
                foreach ($val['children'] as $child) {
                    if ($currentRouteName == $defaultIndexRoute) { // 'doc_default_index.html'
                        $currentPageData = $this->menu[$i];
                        $currentPageData['current_child_index'] = 0;
                    } else if ($path && strpos($child['url'], $path) !== false) {
                        $currentPageData = $this->menu[$i];
                        $currentPageData['current_child_index'] = $c;
                    }
                    ++$c;
                }
            }
            ++$i;
        }
        return $currentPageData;
    }

    /**
     * Returns to previous page data
     * 
     * @return array
     */
    private function getPrevPageData() : array
    {
        $data = $this->getCurrentPageData();
        if (array_key_exists('current_child_index', $data)) {
            $previousIndex = $data['current_child_index'] - 1;
            if (! empty($data['children'][$previousIndex])) {
                return $data['children'][$previousIndex];
            }
        } 
        if (array_key_exists('current_index', $data)) {
            $previousIndex = $data['current_index'] - 1;
            if (! empty($this->menu[$previousIndex])) {
                return $this->menu[$previousIndex];
            }
        }
        return array();
    }

    /**
     * Returns to next page data
     * 
     * @return array
     */
    private function getNextPageData() : array
    {
        $data = $this->getCurrentPageData();
        if (array_key_exists('current_child_index', $data)) {
            $previousIndex = $data['current_child_index'] + 1;
            if (! empty($data['children'][$previousIndex])) {
                return $data['children'][$previousIndex];
            }
        }
        if (array_key_exists('current_index', $data)) {
            $previousIndex = $data['current_index'] + 1;
            if (! empty($this->menu[$previousIndex])) {
                return $this->menu[$previousIndex];
            }
        }
        return array();
    }

    /**
     * Returns to bread crumbs
     * 
     * @return string
     */
    public function getBreadCrumbs(string $indexName = "Index") : string
    {
        $html = '<ol class="breadcrumb">'.PHP_EOL;
            $breadCrumbs = $this->generateBreadCrumbs($indexName);
            foreach ($breadCrumbs as $li) {
                $html.= $li;
            }
        $html.= '</ol>'.PHP_EOL;
        return $html;
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
    public function getPageFooter($prevPageLabel = "", $nextPageLabel = "") : string
    {
        $prevPageData = $this->getPrevPageData();
        $nextPageData = $this->getNextPageData();

        $html = "<nav class=\"pagination-nav\">";
            $html.= "<div class=\"row g-0\">";
                $html.= "<div class=\"col col-6\">";
                            if (! empty($prevPageData)) {
                              $html.= "<div class=\"control\" onclick=\"olodocPrevPage('".$prevPageData['url']."')\">";
                                $html.= "<div>$prevPageLabel</div>";
                                $html.= "<a href=\"javascript:void(0);\">« ".$prevPageData['label']."</a>";
                              $html.= "</div>";
                            }
                $html.= '</div>';
                $html.= '<div class="col col-6">';
                            if (! empty($nextPageData)) {
                                $html.= "<div class=\"control float-end text-end\" onclick=\"olodocNextPage('".$nextPageData['url']."')\">";
                                    $html.= "<div>$nextPageLabel</div>";
                                    $html.= "<a href=\"javascript:void(0);\">".$nextPageData['label']." »</a>";
                                $html.= "</div>";
                            }
                $html.= "</div>";
            $html.= "</div>";
        $html.= "</nav>";
        $html.= $this->generateFooter();
        return $html.PHP_EOL;
    }

    /**
     * Returns to javascript codes
     * 
     * @return string
     */
    public function getJavascript() : string
    {
        //
        // Page navigation js
        //
        $script = "function olodocGoToPage(url) {
            window.location.href = url;
        }
        function olodocPrevPage(url) {
            window.location.href = '%s' + url;
        }
        function olodocNextPage(url) {
            window.location.href = '%s' + url;
        }
        function olodocChangeVersion() {
            var version = document.getElementById(\"version-combobox\").value
            window.location.href = '%s' + version + '/index.html'
        }".PHP_EOL;
        //
        // Search icons js
        //
        $script.= 'document.getElementById("cancel-icon").addEventListener("click", function(){
            document.getElementById("search-input").value = "";
            document.getElementById("cancel-icon").style.display = "none";
            document.getElementById("search-icon").style.display = "block";
            olodocNoSearchResultFound();
        })'.PHP_EOL;
        //
        // Search no result js
        //
        $script.= 'function olodocNoSearchResultFound() {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                var response = JSON.parse(this.responseText);
                var html = \'<div class="no-search-result">\';
                    html += \'<h3 class="no-search-result-title">\' + response.data.title + \'</h3>\';
                    html += \'<div class="no-search-result-item card">\';
                      html += \'<div class="card-body">\';
                        html += response.data.resultText;
                      html += \'</div>\';
                    html += \'</div>\';
                html += \'</div>\';
                document.getElementById("markdown-content").innerHTML = html;
              }
            }
            xmlhttp.open("GET","/search", true);
            xmlhttp.send();
        }'.PHP_EOL;
        //
        // Search results js
        //
        $script.= 'function olodocSearchResult(str) {
            if (str.length == 0) {
              document.getElementById("cancel-icon").style.display = "none";
              document.getElementById("search-icon").style.display = "block";
              return;
            }
            document.getElementById("cancel-icon").style.display = "block";
            document.getElementById("search-icon").style.display = "none";
            if (str.length < 3) {
              olodocNoSearchResultFound();
              return;
            }
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                var response = JSON.parse(this.responseText);
                if (response["data"] 
                    && response["data"]["results"] 
                    && Array.isArray(response["data"]["results"])
                    && response["data"]["results"].length > 0) {
                        var html = \'<div class="search-result">\';
                        html += \'<h3 class="search-result-title">\' + response.data.title + \'</h3>\';
                        html += \'<div class="search-result-text">\' + response.data.resultText + \'</div>\';
                        response["data"]["results"].forEach(function(item) {
                          var url = \'/\' + item.path + \'/\' + item.version + item.file;
                          html += \'<div class="search-result-item card">\';
                          html += \'<a href="\' + url + \'" class="search-result-item-link">\';
                            html += \'<div class="card-body">\';
                              html += \'<blockquote>\';
                              html += item.file;
                              html += \'<footer class="blockquote-footer">\' + item.line + \'</footer>\';
                              html += \'</blockquote>\';
                            html += \'</div>\';
                          html += \'</a>\';
                          html += \'</div>\';
                        });
                      html += \'</div>\';
                      document.getElementById("markdown-content").innerHTML = html;
                } else {
                  olodocNoSearchResultFound()
                } // end if
              } // end ready state
            } // end function
            xmlhttp.open("GET","/search?v=" + encodeURIComponent("%s") + "&q=" + str, true);
            xmlhttp.send();
        }'.PHP_EOL;

        $script.= 'var bsTabEl = document.querySelectorAll(\'button[data-bs-toggle="tab"]\')
            bsTabEl.forEach(function(el){
            el.addEventListener("shown.bs.tab", function (e) {
              Prism.highlightAll(); // init prism for each tab
            })    
        })'.PHP_EOL;

        return sprintf(
            $script,
            $this->baseUrl.$this->version,
            $this->baseUrl.$this->version,
            $this->baseUrl,
            "'/",
            "'",
            $this->version,
        );
    }

    /**
     * Returns to current page links
     *
     * @return string
     */
    public function getCurrentPageLinks()
    {
        return $this->currentPageLinks;
    }

    /**
     * Returns to child page links
     *
     * @return string
     */
    public function getChildPageLinks()
    {
        return $this->childPageLinks;
    }

    /**
     * Generates sub menus using directory
     * 
     * @param  string $directory 
     * @return array
     */
    protected function generateMenu(string $directory = "", string $page = "") : array
    {
        $children = array();
        $folderMenuArray = $this->menuLoader->getFolderableMenuArray();
        $this->title = isset($folderMenuArray[$directory]['label']) ? $folderMenuArray[$directory]['label'] : '';
        $routeName = $this->documentManager->getRouteName();

        $indexRoutes = [
            $this->documentManager::INDEX_ROUTE,
            $this->documentManager::INDEX_DEFAULT,
            $this->documentManager::INDEX_DEFAULT_INDEX,
            $this->documentManager::INDEX_DEFAULT_SLASH,
            $this->documentManager::INDEX_DEFAULT_LATEST,
            $this->documentManager::INDEX_HTML_ROUTE,
        ];
        foreach ($this->menu as $val) {
            $active = '';
            $exp = explode("/", $val['url']);
            $menuPage = end($exp);
            if ($menuPage == $page) {
                $this->subTitle = $val['label'];
                $active = 'active';
            }
            if (! empty($val['children'])) {
                if (!array_key_exists("folder", $val)) {
                    throw new ConfigurationErrorException(
                        "Parent menu of children must be contains folder key."
                    );
                }
                $folderName = mb_strtolower($val['folder']);
                $children[$folderName] = $val['children'];
            }
            $this->currentPageLinks.= '<li class="nav-item">'; 
            $hasFolderIcon = (array_key_exists('folder', $val) && $val['folder']);
            if (! empty($val['children']) || $hasFolderIcon) {
                $this->currentPageLinks.= '<a href="'.$this->baseUrl.$this->version.$val['url'].'" class="nav-link '.$active.'">'.Self::GOOGLE_FOLDER_ICON.'&nbsp;&nbsp;'.$val['label'].'</a>';
            } else {
                $this->currentPageLinks.= '<a href="'.$this->baseUrl.$this->version.$val['url'].'" class="nav-link '.$active.'">'.$val['label'].'</a>';
            }
            if (count($this->data['subItems']) > 0 
                && $menuPage == $page
                && ! in_array($routeName, $indexRoutes) // don't generate anchor items for index routes
            ) {
                if ($page != 'index.html') { // do not generate anchors for introduction pages ...
                    $this->anchorGenerator->generate();
                }
                $anchorItems = $this->anchorGenerator->getAnchorItems();
                if (! empty($anchorItems)) {
                    $this->currentPageLinks.= '<div id="rsb-collapse">';
                    $this->currentPageLinks.= '<ul class="nav flex-column bs-sidenav">';
                        $this->currentPageLinks.= '<ul class="nav-sub">';
                            $this->currentPageLinks.= $anchorItems;
                        $this->currentPageLinks.= '</ul>';
                    $this->currentPageLinks.= '</ul>';
                    $this->currentPageLinks.= '</div>';
                }
            }
            $this->currentPageLinks.= '</li>';  // end nav items
        }
        return $children;
    }

    /**
     * Generates child menus using directory and subDirectory params
     * 
     * @param  string $directory 
     * @return array
     */
    protected function generateChildMenu($children = array(), string $subDirectory = "", string $subPage = "")
    {
        $childMenu = array_key_exists($subDirectory, $children) ? $children[$subDirectory] : array();
        $this->childPageLinks = '';
        foreach ($childMenu as $val) {
            $active = '';
            $explodedUrl = explode("/", $val['url']);
            $childMenuPage = end($explodedUrl);
            if ($childMenuPage == $subPage) {
                $this->subTitle = $val['label'];
                $active = 'active';
            }
            $this->childPageLinks.= '<li class="nav-item">';
            $this->childPageLinks.= '<a href="'.$this->baseUrl.$this->version.$val['url'].'" class="nav-link '.$active.'">'.$val['label'].'</a>';

            if (count($this->data['subItems']) > 0 && $childMenuPage == $subPage) {
                $this->anchorGenerator->generate();
                $this->childPageLinks.= '<div id="rsb-collapse">';
                    $this->childPageLinks.= '<ul class="nav flex-column bs-sidenav">';
                        $this->childPageLinks.= '<ul class="nav-sub">';
                            $this->childPageLinks.= $this->anchorGenerator->getAnchorItems();
                        $this->childPageLinks.= '</ul>';
                    $this->childPageLinks.= '</ul>';
                $this->childPageLinks.= '</div>';
            }
            $this->childPageLinks.= '</li>';  // end nav items
        }
    }

    /**
     * Generate powered by footer
     * 
     * @return string
     */
    private function generateFooter()
    {
        return '<div class="mt-5">
          <div class="pt-4 border-top">
            <p class="text-end">Proudly created by <a href="https://olodoc.dev" target="_blank">Olodoc</a>.</p>
          </div>
        </div>';
    }

    /**
     * Generate page bread crumbs
     *
     * @return string
     */
    protected function generateBreadCrumbs($indexName = "Index")
    {
        $item = '<li class="breadcrumb-item" aria-current="page">';
            $item.= '<a href="'.$this->baseUrl.$this->version.'/index.html">'.$indexName.'</a>';
        $item.= '</li>';
        $i = 0;
        $breadCrumbs = array();
        $breadCrumbs[$i] = $item;
        $routeName = $this->documentManager->getRouteName();
        $directory = $this->documentManager->getDirectory();
        $subDirectory = $this->documentManager->getSubDirectory();
        ++$i;
        switch ($routeName) {
            case $this->documentManager::INDEX_DEFAULT:
            case $this->documentManager::INDEX_DEFAULT_INDEX:
            case $this->documentManager::INDEX_ROUTE:
            case $this->documentManager::INDEX_HTML_ROUTE:
                $breadCrumbs[$i] = '<li class="breadcrumb-item active" aria-current="page">'.$this->getTitle().'</li>';    
                break;
            case $this->documentManager::DIRECTORY_ROUTE:
                $breadCrumbs[$i] = '<li class="breadcrumb-item active" aria-current="page">'.$this->getTitle().'</li>';   
                break;
            case $this->documentManager::SUB_DIRECTORY_ROUTE:
                $item = '<li class="breadcrumb-item active" aria-current="page">';
                $item.= '<a href="'.$this->baseUrl.$this->version.'/'.$directory.'/index.html">'.$this->getTitle().'</a>';
                $item.= '</li>';
                $item.= '<li class="breadcrumb-item active" aria-current="page">';
                $item.= '<a href="'.$this->baseUrl.$this->version.'/'.$directory.'/'.$subDirectory.'/'.$subDirectory.'.html">'.mb_ucfirst($subDirectory).'</a>';
                $item.= '</li>';
                $item.= '<li class="breadcrumb-item active" aria-current="page">'.$this->getSubTitle().'</li>';
                $breadCrumbs[$i] = $item;
                break;
            case $this->documentManager::PAGE_ROUTE:
                $item = '<li class="breadcrumb-item active" aria-current="page">';
                $item.= '<a href="'.$this->baseUrl.$this->version.'/'.$directory.'/index.html">'.$this->getTitle().'</a>';
                $item.= '</li>';
                $item.= '<li class="breadcrumb-item active" aria-current="page">'.$this->getSubTitle().'</li>';
                $breadCrumbs[$i] = $item;
                break;
        }
        return $breadCrumbs;
    }

}
