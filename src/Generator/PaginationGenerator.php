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
 * Pagination Generator
 *
 * Responsible for build paginations on the documentation page
 */
class PaginationGenerator implements PaginationGeneratorInterface
{
    /**
     * Document generator footer
     * 
     * @var string
     */
    private $pageFooter;

    /**
     * Menu Generator class
     * 
     * @var object
     */
    private $menuGenerator;

    /**
     * Document manager
     * 
     * @var object
     */
    private $documentManager;

    /**
     * Menu data
     * 
     * @var array
     */
    private $menu = array();

    /**
     * Constructor
     * 
     * @param DocumentManagerInterface $documentManager object
     * @param MenuGeneratorInterface   $menuGenerator   object
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        MenuGeneratorInterface $menuGenerator
    )
    {
        $this->menu = $menuGenerator->getMenu();
        $this->menuGenerator = $menuGenerator;
        $this->documentManager = $documentManager;
    }

    /**
     * Generates and returns to navigation bar html with footer
     * 
     * @return string
     */
    public function generate($prevPageLabel = "", $nextPageLabel = "") : string
    {
        $pageFooter = $this->generateFooter(); // don't remove this without permission
        $prevPageData = $this->getPrevPageData();
        $nextPageData = $this->getNextPageData();
        $html = "<div class=\"row g-0 no-select\">";
            $html.= "<div class=\"col-6\">";
                        if (! empty($prevPageData['url']) && ! empty($prevPageData)) {
                          $html.= "<div class=\"control\" onclick=\"olodocPrevPage('".$prevPageData['url']."')\">";
                            $html.= "<div class=\"iterator-label\">$prevPageLabel</div>";
                            $html.= "<a href=\"javascript:void(0);\">« ".$prevPageData['label']."</a>";
                          $html.= "</div>";
                        }
            $html.= '</div>';
            $html.= '<div class="col-6">';
                        if (! empty($nextPageData['url']) && ! empty($nextPageData)) {
                            $html.= "<div class=\"control float-end text-end\" onclick=\"olodocNextPage('".$nextPageData['url']."')\">";
                                $html.= "<div class=\"iterator-label\">$nextPageLabel</div>";
                                $html.= "<a href=\"javascript:void(0);\">".$nextPageData['label']." »</a>";
                            $html.= "</div>";
                        }
            $html.= "</div>";    
        $html.= "</div>";
        $html.= $pageFooter;
        return $html.PHP_EOL;
    }

    /**
     * Returns to current page data
     * 
     * @return array
     */
    private function getCurrentPageData() : array
    {
        $version = $this->documentManager->getVersion();
        $currentPath = str_replace(
            $this->documentManager::LATEST_VERSION_NAME, // support for latest version
            $version,
            $this->documentManager->getRequest()->getUri()->getPath()
        );
        $currentPage = $this->documentManager->getPage();
        $currentRouteName = $this->documentManager->getRouteName();
        $defaultIndexRoute = $this->documentManager::INDEX_DEFAULT_INDEX;
        $path = strstr($currentPath, "/".$version."/");
        if (is_string($path)) {
            $path = str_replace(
                $this->documentManager->getVersion(), 
                "",
                $path
            );
        }
        if (! empty($path)) {
            $path = substr($path, 1);  // remove unnecessary slash "//index.html"
        }
        $i = 0;
        $currentPageData = array();
        foreach ($this->menu as $key => $val) {
            if (! empty($val['url']) && $val['url'] == $path) { // Only if we are on the same route build the pagination ..
                $currentPageData = $this->menu[$i];
                $currentPageData['current_index'] = $i;
            }
            ++$i;
        }
        $this->validateFooterText();
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
        if (array_key_exists('current_index', $data)) {
            $previousIndex = $data['current_index'] - 1;
            if (! empty($data['children'][$previousIndex])) {
                return $data['children'][$previousIndex];
            }
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
        if (array_key_exists('current_index', $data)) {
            $previousIndex = $data['current_index'] + 1;
            if (! empty($data['children'][$previousIndex])) {
                return $data['children'][$previousIndex];
            }
            if (! empty($this->menu[$previousIndex])) {
                return $this->menu[$previousIndex];
            }
        }
        return array();
    }

    /**
     * Generate powered by footer
     * 
     * @return string
     */
    private function generateFooter() : string
    {
        $this->pageFooter = '<div style="margin-top: var(--ol-body-footer-margin-top);font-size: var(--ol-body-footer-font-size);">
            <p class="text-end" style="padding-top: var(--ol-body-footer-padding-top);">This document was created with <b><a href="https://olodoc.dev" target="_blank">Olodoc</a></b>.</p>
        </div>';
        return $this->pageFooter;
    }

    /**
     * Validate page footer variable
     * 
     * @return void
     */
    private function validateFooterText()
    {
        if (strpos($this->pageFooter, "Olodoc") === false) {
            die(
                "Please do not remove the 'This document was created with Olodoc' statement in your footer."
            );
        }
    }

}
