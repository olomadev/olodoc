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
    private $documentManager;

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
     * Generate <ol> and <li> tags for breadcrumbs
     * 
     * @return void
     */
    public function generate(string $indexName = "Index") : string
    {
        $html = '<ol class="breadcrumb">'.PHP_EOL;
            $breadCrumbs = $this->generateBody($indexName);
            foreach ($breadCrumbs as $li) {
                $html.= $li;
            }
        $html.= '</ol>'.PHP_EOL;
        return $html;
    }

    /**
     * Generate page bread crumbs without <ol> tags
     *
     * @return string
     */
    public function generateBody(string $indexName = "Index") : array
    {
        $pageLabel = $this->menuGenerator->getPageLabel();
        $segments = $this->menuGenerator->getSegments();
        $baseUrl = $this->documentManager->getBaseUrl();
        $version = $this->documentManager->getVersion();
        //
        // first index item
        // 
        $item = '<li class="breadcrumb-item" aria-current="page">';
            $item.= '<a href="'.$baseUrl.$version.'/index.html">'.$indexName.'</a>';
        $item.= '</li>';
        $i = 0;
        $breadCrumbs = array();
        $breadCrumbs[0] = $item;
        $currentPage = $this->documentManager->getPage();
        $currentRouteName = $this->documentManager->getRouteName();
        switch ($currentRouteName) {
            case $this->documentManager::INDEX_DEFAULT:
            case $this->documentManager::INDEX_DEFAULT_INDEX:
            case $this->documentManager::INDEX_DEFAULT_SLASH:
            case $this->documentManager::INDEX_DEFAULT_LATEST:
                $breadCrumbs[$i] = '<li class="breadcrumb-item active" aria-current="page">'.$pageLabel.'</li>';
                break;
            case $this->documentManager::DIRECTORY_ROUTE:         
                $segmentLevel = count($segments) - 1;
                foreach ($segments as $level => $dirname) {
                    $directoryLabel = Self::getDirectoryLabel($dirname);
                    ++$i;
                    $currentDirectory = ($level > 0) ? implode("/", array_values($segments)) : $dirname;
                    $item = '<li class="breadcrumb-item active" aria-current="page">';
                    if ($currentPage == $this->documentManager::INDEX_PAGE && $segmentLevel == $level) {
                        $item.= $directoryLabel;
                    } else {
                        $item.= '<a href="'.$baseUrl.$version.'/'.$currentDirectory.'/index.html">'.$directoryLabel.'</a>';
                    }
                    $item.= '</li>';
                    $breadCrumbs[$i] = $item;
                }
                break;
        }
        if (count($breadCrumbs) == 1) {
            ++$i;
            $breadCrumbs[$i] = '<li class="breadcrumb-item active" aria-current="page">'.$pageLabel.'</li>';    
        }
        if (! empty($breadCrumbs[$i]) && strip_tags($breadCrumbs[$i]) != $pageLabel) {
            $breadCrumbs[$i+1] = '<li class="breadcrumb-item active" aria-current="page">'.$pageLabel.'</li>';    
        }
        return $breadCrumbs;
    }

    /**
     * Generate directory label
     * 
     * @param  string $dirname name
     * @return string
     */
    protected static function getDirectoryLabel($dirname)
    {
        $dashMap = array_map(function($v) {
            return mb_ucfirst($v);
        }, explode("-", $dirname));
        return implode(" ", $dashMap);
    }

}