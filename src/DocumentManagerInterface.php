<?php

declare(strict_types=1);

namespace Olodoc;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Document Manager Interface
 */
interface DocumentManagerInterface
{
    /**
     * Set available versions for your documents
     * 
     * @param array $versions version names
     */
    public function setAvailableVersions(array $versions);

    /**
     * Returns to available versions of your documents
     *
     * @return array
     */
    public function getAvailableVersions() : array;

    /**
     * Set default version
     * 
     * @param string $version name
     */
    public function setDefaultVersion(string $defaultVersion);

    /**
     * Returns to default version
     *
     * @return string
     */
    public function getDefaultVersion() : string;

    /**
     * Set config path
     *
     * @return void
     */
    public function setConfigPath(string $configPath = '/config/docs/');

    /**
     * Returns to config path
     * 
     * @return string
     */
    public function getConfigPath() : string;

    /**
     * Disable anchor generations
     * 
     * @param  Bool   $bool boolean value
     * @return void
     */
    public function disableAnchorGenerations();

    /**
     * Returns to anchor generations boolean
     *
     * @return boolean
     */
    public function getAnchorGenerations() : Bool;

    /**
     * Set document root path
     *
     * @return void
     */
    public function setRootPath(string $documentRoot);

    /**
     * Returns to root path
     * 
     * @return string
     */
    public function getRootPath() : string;

    /**
     * Set site map xml root path
     *
     * @return void
     */
    public function setXmlMapPath(string $xmlMapPath);

    /**
     * Returns to site map xml path
     * 
     * @return string
     */
    public function getXmlMapPath() : string;
    
    /**
     * Set site map xml root path
     *
     * @return void
     */
    public function setBase64Convert(bool $bool);

    /**
     * Returns to base64 convert boolean
     *
     * @return void
     */
    public function getBase64Convert() : bool;

    /**
     * Set html path
     *
     * @return void
     */
    public function setHtmlPath(string $htmlPath = '/data/docs/');

    /**
     * Set html path
     *
     * @return void
     */
    public function getHtmlPath() : string;

    /**
     * Set menu file
     *
     * @return void
     */
    public function setMenuFile(string $menuFile);

    /**
     * Returns to menu config file
     *
     * @return string
     */
    public function getMenuFile() : string;

    /**
     * Returns to currenct document file path
     * 
     * @return string
     */
    public function getFilePath();

    /**
     * Set request
     * @param object $request Psr\Http\Message\ServerRequestInterface;
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * Get request
     *
     * @return object Psr\Http\Message\ServerRequestInterface; 
     */
    public function getRequest() : ServerRequestInterface;

    /**
     * Set locale
     *
     * @return void
     */
    public function setLocale(string $locale);

    /**
     * Returns to config
     *
     * @return array config
     */
    public function getLocale() : string;

    /**
     * Set version number
     * 
     * @param string $version version number
     */
    public function setVersion(string $version);

    /**
     * Returns to version number
     * 
     * @return string
     */
    public function getVersion() : string;

    /**
     * Set docs base url
     * 
     * @param string $baseUrl doc base url
     */
    public function setBaseUrl(string $baseUrl);

    /**
     * Returns to base url of docs
     * 
     * @return string
     */
    public function getBaseUrl() : string;

    /**
     * Set route name
     *
     * @param string $routeName route name
     */
    public function setRouteName(string $routeName);

    /**
     * Returns to matched route name
     *
     * @return object
     */
    public function getRouteName() : string;

    /**
     * Set route parameters
     * 
     * @param array $routeParams route parameters
     */
    public function setRouteParams(array $routeParams);

    /**
     * Returns to route params
     * 
     * @return array
     */
    public function getRouteParams() : array;

    /**
     * Returns to directory name
     * 
     * @return string|null
     */
    public function getDirectory();

    /**
     * Returns to page name
     * 
     * @return string|null
     */
    public function getPage();

}