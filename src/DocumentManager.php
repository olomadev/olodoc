<?php

declare(strict_types=1);

namespace Olodoc;

use Psr\Http\Message\ServerRequestInterface;
use Olodoc\Exception\ConfigurationErrorException;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Document Manager
 *
 * It is responsible for keeping document settings, config, options, routes, constants etc ..
 */
class DocumentManager implements DocumentManagerInterface
{
    const INDEX_PAGE = 'index.html';
    const INDEX_DEFAULT = 'doc_default_index';
    const INDEX_DEFAULT_SLASH = 'doc_default_index_slash';
    const INDEX_DEFAULT_LATEST = 'doc_default_index_latest';
    const INDEX_DEFAULT_INDEX = 'doc_default_index.html';
    const PAGE_ROUTE = 'doc_page';
    const DIRECTORY_ROUTE = 'doc_directory';
    const LATEST_VERSION_NAME = 'latest';
    const FOLDER_ICON = '<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M168-192q-29 0-50.5-21.5T96-264v-432q0-29.7 21.5-50.85Q139-768 168-768h216l96 96h312q29.7 0 50.85 21.15Q864-629.7 864-600v336q0 29-21.15 50.5T792-192H168Zm0-72h624v-336H450l-96-96H168v432Zm0 0v-432 432Z"/></svg>';
    
    protected $page;
    protected $directory;
    protected $version = '1.0';
    protected $baseUrl = '/docs/';
    protected $locale = "en";
    protected $request;
    protected $routeName;
    protected $routeParams = array();
    protected $menuFile;
    protected $routeFolder = 'docs';
    protected $htmlPath = '/data/docs/';
    protected $configPath = '/config/docs/';
    protected $xmlMapPath = '/public/';
    protected $documentRoot;
    protected $defaultVersion;
    protected $base64Convert = false;
    protected $availableVersions = array();
    protected $disableAnchorGenerations = false;
    protected $disableAnchorsForIndexPages = false;

    /**
     * Set available versions for your documents
     * 
     * @param array $versions version names
     */
    public function setAvailableVersions(array $versions)
    {
        $this->availableVersions = $versions;
    }

    /**
     * Returns to available versions of your documents
     *
     * @return array
     */
    public function getAvailableVersions() : array
    {
        return $this->availableVersions;
    }

    /**
     * Set default version
     * 
     * @param string $version name
     */
    public function setDefaultVersion(string $defaultVersion)
    {
        $this->defaultVersion = $defaultVersion;
    }

    /**
     * Returns to default version
     *
     * @return string
     */
    public function getDefaultVersion() : string
    {
        return $this->defaultVersion;
    }

    /**
     * Set site map xml root path
     *
     * @return void
     */
    public function setXmlMapPath(string $xmlMapPath)
    {
        $this->xmlMapPath = $xmlMapPath;
    }

    /**
     * Returns to site map xml path
     * 
     * @return string
     */
    public function getXmlMapPath() : string
    {
        return rtrim($this->xmlMapPath, '/');
    }

    /**
     * Set site map xml root path
     *
     * @return void
     */
    public function setBase64Convert(bool $bool)
    {
        $this->base64Convert = $bool;
    }

    /**
     * Returns to base64 convert boolean
     *
     * @return void
     */
    public function getBase64Convert() : bool
    {
        return $this->base64Convert;
    }

    /**
     * Set document root path
     *
     * @return void
     */
    public function setRootPath(string $documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * Returns to root path
     * 
     * @return string
     */
    public function getRootPath() : string
    {
        return rtrim($this->documentRoot, '/');
    }

    /**
     * Set route folder
     * 
     * @param string $route path
     */
    public function setRouteFolder(string $folder = 'docs')
    {
        $this->routeFolder = $folder;
    }

    /**
     * Returns to route folder name
     *
     * @return string folder name
     */
    public function getRouteFolder() : string
    {
        return ltrim(rtrim($this->routeFolder, '/'), '/');
    }

    /**
     * Set config path
     *
     * @return void
     */
    public function setConfigPath(string $configPath = '/config/docs/')
    {
        $this->configPath = $configPath;
    }

    /**
     * Returns to config path
     * 
     * @return string
     */
    public function getConfigPath() : string
    {
        return ltrim(rtrim($this->configPath, '/'), '/');
    }

    /**
     * Disables anchor generations
     * 
     * @param  boolean $bool bool
     * @return void
     */
    public function disableAnchorGenerations($bool = true)
    {
        $this->disableAnchorGenerations = $bool;
    }

    /**
     * Returns to anchor generations boolean
     *
     * @return boolean
     */
    public function getAnchorGenerations() : Bool
    {
        return $this->disableAnchorGenerations;
    }

    /**
     * Disables anchor generations for index
     * 
     * @param  boolean $bool bool
     * @return void
     */
    public function disableAnchorsForIndexPages($bool = true)
    {
        $this->disableAnchorsForIndexPages = $bool;
    }

    /**
     * Returns to disable anchor generations for index boolean
     *
     * @return boolean
     */
    public function getAnchorsForIndexPages() : Bool
    {
        return $this->disableAnchorsForIndexPages;
    }

    /**
     * Set html path
     *
     * @return void
     */
    public function setHtmlPath(string $htmlPath = '/data/docs/')
    {
        $this->htmlPath = $htmlPath;
    }

    /**
     * Set html path
     *
     * @return void
     */
    public function getHtmlPath() : string
    {
        return ltrim(rtrim($this->htmlPath, '/'), '/');
    }

    /**
     * Set menu file
     *
     * @return void
     */
    public function setMenuFile(string $menuFile)
    {
        $this->menuFile = $menuFile;
    }

    /**
     * Returns to menu config file
     *
     * @return string
     */
    public function getMenuFile() : string
    {
        if (empty($this->documentRoot)) {
            throw new ConfigurationErrorException(
                "Configuration Error: setRootPath() method must be set at top level."
            );
        }
        if (empty($this->configPath)) {
            throw new ConfigurationErrorException(
                "Configuration Error: setConfigPath() method must be set at top level."
            );
        }
        $file = $this->getRootPath().'/'.$this->getConfigPath().'/'.$this->getVersion().'/'.$this->getLocale().'/menu.php';
        return $file;
    }

    /**
     * Returns to currenct document file path
     * 
     * @return string
     */
    public function getFilePath()
    {
        $routeName = $this->getRouteName();
        if (empty($routeName)) {
            throw new ConfigurationErrorException(
                "Configuration Error: setRouteName() method must be set at top level."
            );
        }
        $basePath = $this->getRootPath().'/'.$this->getHtmlPath().'/'.$this->getVersion().'/'.$this->getLocale();
        switch ($routeName) {
            case Self::INDEX_DEFAULT:
            case Self::INDEX_DEFAULT_INDEX:
            case Self::INDEX_DEFAULT_SLASH:
            case Self::INDEX_DEFAULT_LATEST:
                $path = $basePath.'/index.html';
                break;
            case Self::PAGE_ROUTE:
                $path = $basePath.'/'.$this->getPage();
                break;
            case Self::DIRECTORY_ROUTE:
                $path = $basePath.'/'.$this->getDirectory().'/'.$this->getPage();
                break;
        }
        return $path;
    }

    /**
     * Set request
     * @param object $request Psr\Http\Message\ServerRequestInterface;
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get request
     *
     * @return object Psr\Http\Message\ServerRequestInterface; 
     */
    public function getRequest() : ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Set locale
     *
     * @return void
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * Returns to config
     *
     * @return array config
     */
    public function getLocale() : string
    {
        return $this->locale;
    }

    /**
     * Set version number
     * 
     * @param string $version version number
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
    }

    /**
     * Returns to version number
     * 
     * @return string
     */
    public function getVersion() : string
    {
        return $this->version;
    }

    /**
     * Set docs base url
     * 
     * @param string $baseUrl doc base url
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Returns to base url of docs
     * 
     * @return string
     */
    public function getBaseUrl() : string
    {
        return rtrim($this->baseUrl, '/').'/';
    }

    /**
     * Set route name
     *
     * @param string $routeName route name
     */
    public function setRouteName(string $routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * Returns to matched route name
     *
     * @return object
     */
    public function getRouteName() : string
    {
        return $this->routeName;
    }

    /**
     * Set route parameters
     * 
     * @param array $routeParams route parameters
     */
    public function setRouteParams(array $routeParams)
    {
        $isDefaultVersion = (empty($routeParams['version']) || $routeParams['version'] == Self::LATEST_VERSION_NAME);
        $defaultVersion = $this->getDefaultVersion();
        $this->version =  $isDefaultVersion ? $defaultVersion : $routeParams['version'];
        $this->directory = empty($routeParams['directory']) ? '' : $routeParams['directory'];
        $this->page = empty($routeParams['page']) ? '' : $routeParams['page'];
        $this->routeParams = $routeParams;
    }

    /**
     * Returns to route params
     * 
     * @return array
     */
    public function getRouteParams() : array
    {
        return $this->routeParams;
    }

    /**
     * Returns to directory name
     * 
     * @return string|null
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Returns to page name
     * 
     * @return string|null
     */
    public function getPage()
    {
        return $this->page;
    }

}