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
     * Page title
     * 
     * @var string
     */
    protected $title;

    /**
     * Page sub title
     * 
     * @var string
     */
    protected $subTitle;

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
     * Directory label
     * 
     * @var string
     */
    protected $directoryLabel = "";

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
        $directory = $this->documentManager->getDirectory();
        $pageRoute = $this->documentManager::PAGE_ROUTE;

        $this->buildSegments($directory);
        $this->buildDirectoryLabel();
        $this->title = count($this->segments) > 1 ? $this->directoryLabel : mb_ucfirst($directory);
        $routeName = $this->documentManager->getRouteName();
        $pages = [
            '/'.$directory.'/'.$page,
            '/'.$page
        ];
        foreach ($this->menu as $val) {
            $active = '';
            if (in_array($val['url'], $pages)) {
                $this->subTitle = $val['label'];
                $active = 'active';
            }
            if (! empty($val['children'])) {
                if (! array_key_exists("folder", $val)) {
                    throw new ConfigurationErrorException(
                        "Parent menu of children must be contains folder key."
                    );
                }
            }
            $hasFolderIcon = (array_key_exists('folder', $val) && $val['folder']);
            if (! empty($val['children']) || $hasFolderIcon) {
                $navFolderClass = ($routeName == $pageRoute && $page == $this->documentManager::INDEX_PAGE) ? 'nav-folder-index' : 'nav-folder';
                $this->sideNavbarLinks.= "<li class=\"$navFolderClass nav-item\">"; 
                $this->sideNavbarLinks.= '<a href="'.$this->baseUrl.$this->version.$val['url'].'" class="nav-link '.$active.'">'.$this->documentManager::FOLDER_ICON.'&nbsp;&nbsp;'.$val['label'].'</a>';
            } else {
                $this->sideNavbarLinks.= '<li class="nav-item">'; 
                $this->sideNavbarLinks.= '<a href="'.$this->baseUrl.$this->version.$val['url'].'" class="nav-link '.$active.'">'.$val['label'].'</a>';
            }
            $this->generateSubItems($val['url'], $pages, $page);
            $this->sideNavbarLinks.= '</li>';  // end nav items
        }
        return $this->data;
    }

    /**
     * Generate sub items of the menu
     * 
     * @param  string $pageUrl page url
     * @param  array  $pages   defined pages
     * @param  string $page    current page
     * @return void
     */
    protected function generateSubItems(string $pageUrl, array $pages, string $page = "")
    {
        $currentRouteName = $this->documentManager->getRouteName();
        $disableAnchorGenerations = $this->documentManager->getAnchorGenerations();
        $disableAnchorsForIndexPages = $this->documentManager->getAnchorsForIndexPages();
        
        if (count($this->data['subItems']) > 0 
            && in_array($pageUrl, $pages)
            && ! in_array($currentRouteName, $this->indexRoutes) // don't generate anchor items for index routes
        ) {
            if (false == $disableAnchorGenerations) {
                if (false == $disableAnchorsForIndexPages 
                    && $page != $this->documentManager::INDEX_PAGE) {  // do not generate anchors for introduction pages ...
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
     * Returns to translated page title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns to translated sub page title
     *
     * @return string
     */
    public function getSubTitle()
    {
        return $this->subTitle;
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
     * Returns to directory label
     * 
     * @return string
     */
    public function getDirectoryLabel() : string
    {
        return $this->directoryLabel;
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
     * @param  string $directory dir path
     * @return void
     */
    protected function buildSegments(string $directory)
    {
        $i = 0;
        $directories = explode("/", $directory);
        foreach ($directories as $dirname) {
            $this->segments[$i] = mb_strtolower($dirname);
            ++$i;  
        }
    }

    /**
     * Build directory labels
     * 
     * @return void
     */
    protected function buildDirectoryLabel()
    {
        $directoryMap = array_map(function($value) {
            return mb_ucfirst($value);
        }, $this->segments);
        $this->directoryLabel = implode(" / ", $directoryMap);
    }

}
