<?php

declare(strict_types=1);

namespace Olodoc;

use Psr\Http\Message\ServerRequestInterface;
use Laminas\I18n\Translator\TranslatorInterface;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Document Manager Interface
 */
interface DocumentManagerInterface
{
    /**
     * Returns to translator class
     * 
     * @return object
     */
    public function getTranslator() : TranslatorInterface;

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
     * Set available languages
     * 
     * @param array $locales language keys
     */
    public function setAvailableLocales(array $locales);

    /**
     * Returns to available locales
     *
     * @return array
     */
    public function getAvailableLocales() : array;

    /**
     * Set default locale
     * 
     * @param string $locale locale
     */
    public function setDefaultLocale(string $defaultLocale);

    /**
     * Returns to default locale
     *
     * @return string
     */
    public function getDefaultLocale() : string;

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
     * Enable/Disable remove default locale from base url
     * 
     * @param bool $bool boolean
     */
    public function setRemoveDefaultLocale(bool $bool);

    /**
     * Returns to anchor generations boolean
     *
     * @return boolean
     */
    public function getRemoveDefaultLocale() : Bool;

    /**
     * Set dom xpath anchor parse query string
     * 
     * @param string $anchorParseQuery query string
     */
    public function setAnchorParseQuery(string $anchorParseQuery);

    /**
     * Returns to dom xpath anchor parse query string
     *
     * @return string
     */
    public function getAnchorParseQuery() : string;

    /**
     * Enable/Disable anchor generations
     * 
     * @param  boolean $bool bool
     * @return void
     */
    public function setAnchorGenerations(bool $bool);

    /**
     * Returns to anchor generations boolean
     *
     * @return boolean
     */
    public function getAnchorGenerations() : Bool;

    /**
     * Enable/Disable anchor generations for index
     * 
     * @param  boolean $bool bool
     * @return void
     */
    public function setAnchorsForIndexPages(bool $bool);

    /**
     * Returns to disable anchor generations for index boolean
     *
     * @return boolean
     */
    public function getAnchorsForIndexPages() : Bool;

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
     * 
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
     * Set http prefix
     * 
     * @param string $httpPrefix http(s)://
     */
    public function setHttpPrefix(string $httpPrefix);

    /**
     * Returns to http prefix
     *
     * @param string
     */
    public function getHttpPrefix() : string;

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