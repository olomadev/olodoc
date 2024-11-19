<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use Olodoc\DocumentManagerInterface;

/**
 * @author Oloma <support@oloma.dev>
 *
 * BreadCrumb Generator
 *
 * Responsible for creating breadcrumbs on the documentation page
 */
class BreadCrumbGenerator implements BreadCrumbGeneratorInterface
{
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
    protected $documentManager;    

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
        $this->menuGenerator = $menuGenerator;
        $this->documentManager = $documentManager;
    }


    /**
     * Returns to bread crumbs
     * 
     * @return string
     */
    public function generate(string $indexName = "Index") : string
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
     * Generate page bread crumbs
     *
     * @return string
     */
    protected function generateBreadCrumbs($indexName = "Index")
    {
        $title = $this->menuGenerator->getTitle();
        $subTitle = $this->menuGenerator->getSubTitle();
        $segments = $this->menuGenerator->getSegments();
        $baseUrl = $this->documentManager->getBaseUrl();
        $version = $this->documentManager->getVersion();
        $item = '<li class="breadcrumb-item" aria-current="page">';
            $item.= '<a href="'.$baseUrl.$version.'/index.html">'.$indexName.'</a>';
        $item.= '</li>';
        $i = 0;
        $breadCrumbs = array();
        $breadCrumbs[$i] = $item;
        $currentPage = $this->documentManager->getPage();
        $currentRouteName = $this->documentManager->getRouteName();
        $currentDirectory = $this->documentManager->getDirectory();
        switch ($currentRouteName) {
            case $this->documentManager::INDEX_DEFAULT:
            case $this->documentManager::INDEX_DEFAULT_INDEX:
            case $this->documentManager::INDEX_DEFAULT_SLASH:
            case $this->documentManager::INDEX_DEFAULT_LATEST:
                $breadCrumbs[$i] = '<li class="breadcrumb-item active" aria-current="page">'.$title.'</li>';
                if (! empty($subTitle)) {
                    $breadCrumbs[$i] = '<li class="breadcrumb-item active" aria-current="page">'.$subTitle.'</li>';
                }
                break;
            case $this->documentManager::PAGE_ROUTE:
                $breadCrumbs[$i] = '<li class="breadcrumb-item active" aria-current="page">'.$title.'</li>';   
                break;
            case $this->documentManager::DIRECTORY_ROUTE:         
                $segmentIndex = 0;
                $segmentLevel = count($segments) - 1;
                foreach ($segments as $level => $dirname) {
                    ++$i;
                    $currentDirectory = ($level > 0) ? implode("/", array_values($segments)) : $dirname;
                    $item = '<li class="breadcrumb-item active" aria-current="page">';
                    if ($currentPage == $this->documentManager::INDEX_PAGE && $segmentLevel == $level) {
                        $item.= mb_ucfirst($dirname);
                    } else {
                        $item.= '<a href="'.$baseUrl.$version.'/'.$currentDirectory.'/index.html">'.mb_ucfirst($dirname).'</a>';
                    }
                    $item.= '</li>';
                    $breadCrumbs[$i] = $item;
                    ++$segmentIndex;
                }
                $item.= '<li class="breadcrumb-item active" aria-current="page">'.$subTitle.'</li>';
                $breadCrumbs[$i] = $item; 
                break;
        }
        return $breadCrumbs;
    }


}
