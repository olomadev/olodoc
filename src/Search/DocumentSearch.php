<?php

declare(strict_types=1);

namespace Olodoc\Search;

use SplFileInfo;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Olodoc\DocumentManagerInterface;
use Olodoc\Exception\SearchFileReadException;
use Olodoc\Exception\FileNotReadableException;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Document Search
 */
class DocumentSearch implements DocumentSearchInterface
{
    protected $locale;
    protected $version;
    protected $baseUrl;
    protected $results = array();
    protected $rootPath;
    protected $htmlPath;
    protected $currentPath;
    protected $queryString;
    protected $hliteOpenTag = "<span style=\"background-color: yellow;\">";
    protected $hliteCloseTag = "</span>";
    protected $documentManager;

    /**
     * Constructor
     * 
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;

        $this->locale = $documentManager->getLocale();
        $this->baseUrl = $documentManager->getBaseUrl();
        $this->version = $documentManager->getVersion();
        $this->rootPath = $documentManager->getRootPath();
        $this->htmlPath = $documentManager->getHtmlPath();
        $this->currentPath = $this->rootPath."/".$this->htmlPath."/".$this->version."/".$this->locale;
    }

    /**
     * Set hlite open tag
     * 
     * @param string $tagOpen html tag
     */
    public function setHliteOpenTag(string $tagOpen)
    {
        $this->hliteOpenTag = $tagOpen;
    }

    /**
     * Set hlite close tag
     * 
     * @param string $tagOpen html tag
     */
    public function setHliteCloseTag(string $tagClose)
    {
        $this->hliteCloseTag = $tagClose;
    }

    /**
     * Returns to hlite open tag
     * 
     * @return string
     */
    public function getHliteOpenTag() : string
    {
        return $this->hliteOpenTag;
    }

    /**
     * Returns to hlite close tag
     * 
     * @return string
     */
    public function getHliteCloseTag() : string
    {
        return $this->hliteCloseTag;
    }

    /**
     * Returns to search keywords
     * 
     * @return string
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * Start searching static files by reading them line by line
     * 
     * @param  string $query words
     * @return array
     */
    public function search(string $query) : array
    {
        $query = filter_var($query, FILTER_SANITIZE_SPECIAL_CHARS);
        $this->queryString = $query;
        $keywords = explode(" ", $query);
        if (is_array($keywords) && count($keywords) > 0) {
            // 
            // https://stackoverflow.com/questions/2483844/highlight-the-word-in-the-string-if-it-contains-the-keyword
            // 
            foreach ($keywords as $keyword) {
                if (empty($keyword)) {
                    break;
                }
                $iterator = new RecursiveDirectoryIterator($this->currentPath);
                foreach (new RecursiveIteratorIterator($iterator) as $splFileInfo) {
                    $file = $splFileInfo->getPathName();
                    $parts = pathinfo($file);
                    $extension = strtolower($parts['extension']);
                    if ($extension != "html") {
                        continue;
                    }
                    $this->readLineByLine($keyword, $file);
                }
            }
        }
        return $this->results;
    }

    /**
     * Read file by line by line
     * 
     * @param  string $keyword keyword
     * @param  string $file    full file path
     * @return void
     */
    protected function readLineByLine(string $keyword, string $file)
    {
        $filename = str_replace($this->currentPath, "", $file); // get the safe filename
        if (! is_readable($file)) {
            throw new FileNotReadableException(
                sprintf(
                    "Search file %s not readable.",
                    $filename
                )
            );
        }
        $fileHandle = fopen($file, 'r');
        if ($fileHandle === false) {
            throw new SearchFileReadException(
                sprintf(
                    "Error opening the file %s.",
                    $filename
                )
            );
        }
        $i = 0;
        while (($line = fgets($fileHandle)) !== false) {
            if (stripos($line, $keyword) !== false) {
                $line = Self::getStrippedLine($line);
                if (! empty($line)) {
                    $this->results[$i] = [
                        'baseUrl' => rtrim($this->baseUrl, "/"),
                        'version' => $this->version,
                        'file' => $filename,
                        'line' => $this->getHlitedLine($keyword, $line),
                    ];
                    ++$i;
                }
            }
        }
        fclose($fileHandle);
    }

    /**
     * Returns to highlighted keyword
     * 
     * @param  string $keyword search keyword
     * @param  string $line    hlighted line
     * @return string
     */
    protected function getHlitedLine(string $keyword, string $line) : string
    {
        $replaceWith = $this->getHliteOpenTag()."$0".$this->getHliteCloseTag();
        return preg_replace(
            "/\p{L}*?".preg_quote($keyword)."\p{L}*/ui", 
            $replaceWith,
            $line
        );
    }

    /**
     * Returns to stripped html file line
     * 
     * @param  string $line html file line
     * @return string
     */
    protected static function getStrippedLine(string $line)
    {                            
        $line = strip_tags(html_entity_decode($line));
        $line = str_replace(["\r", "\n"], '', $line);
        return trim($line);
    }

}