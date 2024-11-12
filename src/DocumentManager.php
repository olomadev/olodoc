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
 * It is responsible for creating document settings, optipns etc ..
 */
class DocumentManager implements DocumentManagerInterface
{
    const INDEX_DEFAULT = 'doc_default_index';
    const INDEX_DEFAULT_SLASH = 'doc_default_index_slash';
    const INDEX_DEFAULT_LATEST = 'doc_default_index_latest';
    const INDEX_DEFAULT_INDEX = 'doc_default_index.html';
    const INDEX_ROUTE = 'doc_index';
    const INDEX_HTML_ROUTE = 'doc_index.html';
    const DIRECTORY_ROUTE = 'doc_directory';
    const SUB_DIRECTORY_ROUTE = 'doc_sub_directory';
    const PAGE_ROUTE = 'doc_page';
    const LATEST_VERSION_NAME = 'latest';

    protected $version = '1.0';
    protected $baseUrl = '/docs/';
    protected $locale = "en";
    protected $request;
    protected $routeName;
    protected $routeParams = array();
    protected $menuFile;
    protected $documentFolder = 'docs';
    protected $htmlPath = '/data/docs/';
    protected $configPath = '/config/docs/';
    protected $xmlMapPath = '/public/';
    protected $documentRoot;
    protected $defaultVersion;
    protected $base64Convert = false;
    protected $directory;
    protected $subDirectory;
    protected $subPage;
    protected $page;
    protected $availableVersions = array();

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
     * Set document folder
     * 
     * @param string $route path
     */
    public function setDocumentFolder(string $folder = 'docs')
    {
        $this->documentFolder = $folder;
    }

    /**
     * Returns to document folder name
     *
     * @return string folder name
     */
    public function getDocumentFolder() : string
    {
        return ltrim(rtrim($this->documentFolder, '/'), '/');
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
        $rootPath = $this->getRootPath();
        $htmlPath = $this->getHtmlPath();
        $locale = $this->getLocale();
        $version = $this->getVersion();
        $directory = $this->getDirectory();
        $subDirectory = $this->getSubDirectory();
        $subPage = $this->getSubPage();
        $page = $this->getPage();
        switch ($routeName) {
            case Self::INDEX_DEFAULT:
            case Self::INDEX_DEFAULT_INDEX:
            case Self::INDEX_DEFAULT_SLASH:
            case Self::INDEX_DEFAULT_LATEST:
            case Self::INDEX_HTML_ROUTE:
                $path = $rootPath.'/'.$htmlPath.'/'.$version.'/'.$locale.'/index.html';
                break;
            case Self::INDEX_ROUTE:
                $path = $rootPath.'/'.$htmlPath.'/'.$version.'/'.$locale.'/'.$page;
                break;
            case Self::DIRECTORY_ROUTE:
                $path = $rootPath.'/'.$htmlPath.'/'.$version.'/'.$locale.'/'.$directory.'/index.html';
                break;
            case Self::SUB_DIRECTORY_ROUTE:
                $path = $rootPath.'/'.$htmlPath.'/'.$version.'/'.$locale.'/'.$directory.'/'.$subDirectory.'/'.$subPage;
                break;
            case Self::PAGE_ROUTE:
                $path = $rootPath.'/'.$htmlPath.'/'.$version.'/'.$locale.'/'.$directory.'/'.$page;
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
        $defaultVersion = $this->getDefaultVersion();
        $this->version = (empty($routeParams['version']) || $routeParams['version'] == Self::LATEST_VERSION_NAME) ? $defaultVersion : $routeParams['version'];
        $this->directory = empty($routeParams['directory']) ? '' : $routeParams['directory'];
        $this->subDirectory = empty($routeParams['sub_directory']) ? '' : $routeParams['sub_directory'];
        $this->subPage = empty($routeParams['sub_page']) ? '' : $routeParams['sub_page'];
        $this->page = empty($routeParams['page']) ? '' : $routeParams['page'];
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
     * Returns to sub directory name
     * 
     * @return string|null
     */
    public function getSubDirectory()
    {
        return $this->subDirectory;
    }

    /**
     * Returns to sub page name
     * 
     * @return string|null
     */
    public function getSubPage()
    {
        return $this->subPage;
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