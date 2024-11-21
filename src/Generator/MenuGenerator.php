<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use Olodoc\Loader\FileLoader;
use Olodoc\Loader\MenuLoader;
use Olodoc\DocumentManagerInterface;
use Olodoc\Exception\ConfigurationErrorException;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Menu Generator
 *
 * Responsible for creating menu on the documentation page
 */
class MenuGenerator implements MenuGeneratorInterface
{
    /**
     * Menu data
     * 
     * @var array
     */
    protected $menu = array();

    /**
     * Page data
     * 
     * @var array
     */
    protected $data = array();

    /**
     * Menu loader class
     *  
     * @var object
     */
    protected $menuLoader;

    /**
     * File loader class
     *
     * @var object
     */
    protected $fileLoader;

    /**
     * Index routes
     * 
     * @var array
     */
    protected $indexRoutes = array();

    /**
     * Segments
     * 
     * @var array
     */
    protected $segments = array();

    /**
     * Base url
     * 
     * @var string
     */
    protected $baseUrl;

    /**
     * Version number
     * 
     * @var string
     */
    protected $version;

    /**
     * All links on the menu
     * 
     * @var string
     */
    protected $sideNavbarLinks;

    /**
     * Document manager
     * 
     * @var object
     */
    protected $documentManager;

    /**
     * Anchor Generator
     * 
     * @var object
     */
    protected $anchorGenerator;

    /**
     * Directory label
     * 
     * @var string
     */
    protected $directoryLabel = "";

    /**
     * Current page label
     * 
     * @var string
     */
    protected $pageLabel = "";

    /**
     * Constructor
     * 
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->baseUrl = $documentManager->getBaseUrl();
        $this->version = $documentManager->getVersion();

        $this->documentManager = $documentManager;
        $this->anchorGenerator = new AnchorGenerator($documentManager);
        $this->menuLoader = new MenuLoader($documentManager);
        $this->fileLoader = new FileLoader($documentManager);

        $htmlOutput = $this->fileLoader->loadFile($documentManager->getFilePath());
        $this->menu = $this->menuLoader->loadMenu($documentManager->getDirectory());
        $this->data = $this->anchorGenerator->parse($htmlOutput);
        $this->data['html'] = '<div id="markdown-content">'.PHP_EOL.$this->data['html'].'</div>'.PHP_EOL;

        $this->indexRoutes = [
            $documentManager::INDEX_DEFAULT,
            $documentManager::INDEX_DEFAULT_INDEX,
            $documentManager::INDEX_DEFAULT_SLASH,
            $documentManager::INDEX_DEFAULT_LATEST,
        ];
    }

    /**
     * Generates sub menus using directory and page inputs
     * 
     * @return array
     */
    public function generate() : array
    {   
        $page = $this->documentManager->getPage();
        $pageRoute = $this->documentManager::PAGE_ROUTE;
        $indexPage = $this->documentManager::INDEX_PAGE;
        $icon = $this->documentManager::FOLDER_ICON;

        $this->buildSegments();
        $this->buildDirectoryLabel();

        $routeName = $this->documentManager->getRouteName();
        $pages = [
            '/'.$this->documentManager->getDirectory().'/'.$page,
            '/'.$page
        ];
        $i = 0;
        $isIndexRoute = in_array($routeName, $this->indexRoutes);
        foreach ($this->menu as $val) {
            if (! empty($val['url'])) {
                $active = ($isIndexRoute && $i == 0) ? 'active' : ''; // active first menu if page == index
                if (in_array($val['url'], $pages)) {
                    $this->pageLabel = empty($val['label']) ? "" : mb_ucfirst($val['label']);
                    $active = 'active';
                }
                if (! empty($val['children'])) {
                    $this->validateParentFolder($val);
                    $navFolderClass = ($routeName == $pageRoute && $page == $indexPage) ? 'nav-folder-index' : 'nav-folder';
                    $this->sideNavbarLinks.= "<li class=\"$navFolderClass nav-item\">"; 
                    $this->sideNavbarLinks.= '<a href="'.$this->baseUrl.$this->version.$val['url'].'" class="nav-link '.$active.'">'.$icon.'&nbsp;&nbsp;'.$val['label'].'</a>';
                } else {
                    $this->sideNavbarLinks.= '<li class="nav-item">'; 
                    $this->sideNavbarLinks.= '<a href="'.$this->baseUrl.$this->version.$val['url'].'" class="nav-link '.$active.'">'.$val['label'].'</a>';
                }
                $this->generateAnchors($val['url'], $pages, $page);
                $this->sideNavbarLinks.= '</li>';  // end nav items
                ++$i;
            }
        }
        return $this->data;
    }

    /**
     * Generate anchors of current page
     * 
     * @param  string $pageUrl page url
     * @param  array  $pages   defined pages
     * @param  string $page    current page
     * @return void
     */
    protected function generateAnchors(string $pageUrl, array $pages, string $page = "")
    {
        $currentRouteName = $this->documentManager->getRouteName();
        $anchorGenerations = $this->documentManager->getAnchorGenerations();
        $anchorsForIndexPages = $this->documentManager->getAnchorsForIndexPages();
        
        if (count($this->data['subItems']) > 0 && in_array($pageUrl, $pages)) {
            if ($anchorGenerations) {
                if (false == $anchorsForIndexPages) {
                  if ($page != $this->documentManager::INDEX_PAGE // do not generate anchors for introduction pages ...
                    && ! in_array($currentRouteName, $this->indexRoutes)) {
                        $this->anchorGenerator->generate();
                    }
                } else {
                    $this->anchorGenerator->generate();
                }
            }
            $anchorItems = $this->anchorGenerator->getAnchorItems();
            if (! empty($anchorItems)) {
                $this->sideNavbarLinks.= '<div id="rsb-collapse">';
                $this->sideNavbarLinks.= '<ul class="nav flex-column bs-sidenav">';
                    $this->sideNavbarLinks.= '<ul class="nav-sub">';
                        $this->sideNavbarLinks.= $anchorItems;
                    $this->sideNavbarLinks.= '</ul>';
                $this->sideNavbarLinks.= '</ul>';
                $this->sideNavbarLinks.= '</div>';
            }
        }
    }

    /**
     * Returns to menu array
     * 
     * @return array
     */
    public function getMenu() : array
    {
        return $this->menu;
    }

    /**
     * Returns to current directory label
     * 
     * @return string
     */
    public function getDirectoryLabel() : string
    {
        return $this->directoryLabel;
    }

    /**
     * Returns to curremt page label
     * 
     * @return string
     */
    public function getPageLabel() : string
    {
        return $this->pageLabel;
    }

    /**
     * Returns sidebar links
     *
     * @return string
     */
    public function getSideNavbarLinks() : string
    {
        return $this->sideNavbarLinks;
    }

    /**
     * Returns to segments
     * 
     * @return array
     */
    public function getSegments() : array
    {
        return $this->segments;
    }

    /**
     * Returns to sidebar header links
     * 
     * @param  string $indexText      index translation
     * @param  string $backToMenuText back to menu link translation
     * @return string
     */
    public function getSidebarHeader($indexText = "Index", $backToMenuText = "Back to Menu") : string
    {
        $html = "";
        $directory = $this->documentManager->getDirectory();
        $currentRouteName = $this->documentManager->getRouteName();
        if ($currentRouteName == $this->documentManager::DIRECTORY_ROUTE) {
            $link = $this->getGoToBackLink();
            $html.= "<div class=\"row g-0 no-select\">";
                $html.= "<div class=\"control\" style=\"width: 100%\" onclick=\"olodocGoToPage('".$link."')\">";
                        $html.= "<div class=\"col col-12\">";
                            $html.= '<div class="folder-label">'.$this->getDirectoryLabel().'</div>';
                            $html.= '<div><a href="javascript:void;">«&nbsp;'.$backToMenuText.'</a></div>';
                        $html.= "</div>";
                $html.= '</div>';
            $html.= "</div>";
        } else {
            $html.= "<div class=\"row g-0 no-select\">";
                $html.= "<div class=\"control\" style=\"width: 100%\">";
                        $html.= "<div class=\"col col-12\">";
                            $html.= '<div class="folder-label">'.$indexText.'</div>';
                        $html.= "</div>";
                $html.= '</div>';
            $html.= "</div>";
        }
        return $html;
    }

    /**
     * Returns to go back link
     *
     * @return string
     */
    public function getGoToBackLink() : string
    {
        $segments = $this->segments;
        $segmentCount = count($segments);
        $link = $this->baseUrl.$this->version."/".$this->documentManager::INDEX_PAGE;
        if ($segmentCount > 1) {
            array_pop($segments); // remove last (current page) segment 
            $backToMenuDirectory = implode("/", $segments);
            $link = $this->baseUrl.$this->version."/".$backToMenuDirectory."/".$this->documentManager::INDEX_PAGE;
        }
        return $link;
    }

    /**
     * Build segment array
     * 
     * @return void
     */
    protected function buildSegments()
    {
        $i = 0;
        $directory = $this->documentManager->getDirectory();
        $directories = explode("/", $directory);
        foreach ($directories as $dirname) {
            $this->segments[$i] = mb_strtolower($dirname);
            ++$i;  
        }
    }

    /**
     * Build directory label
     * 
     * @return void
     */
    protected function buildDirectoryLabel()
    {
        if (count($this->segments) > 1) {
            $directoryMap = array_map(function($value) {
                return mb_ucfirst($value);
            }, $this->segments);
            $this->directoryLabel = implode(" / ", $directoryMap);
        } else {
            $directoryKey = $this->documentManager->getDirectory();
            $this->directoryLabel = mb_ucfirst($directoryKey);
        }
    }

    /**
     * Validate parent folder value
     * 
     * @param  array $val configuration value
     * @return void
     */
    protected function validateParentFolder(array $val)
    {
        if (! array_key_exists("folder", $val)) {
            throw new ConfigurationErrorException(
                "Parent menu of children must be contains folder key."
            );
        }
        if (empty($val['folder'])) {
            throw new ConfigurationErrorException(
                "The folder in the children's parent menu must contain a value."
            );
        }
    }

}
