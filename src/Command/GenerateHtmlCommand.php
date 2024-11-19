<?php

namespace Olodoc\Command;

use Exception;
use Olodoc\DocumentManagerFactory;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateHtmlCommand extends Command
{
    private $config;
    private $baseUrl;
    private $rootPath;
    private $htmlPath;
    private $httpPrefix;
    private $imagesFolder;
    private $configArray = array();
    private $availableLanguages = array();

    public function __construct(array $config)
    {
        $this->configArray = $config;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Creates documentation files by converting ".md" files to html.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($this->configArray['olodoc'])) {
            $output->writeln('<error>Olodoc configuration key not found in your config file.</error>');
            return Command::FAILURE;
        }
        $this->config = $this->configArray['olodoc'];
        try {
            $this->validateConfigurations();
            $this->removeHtmlFiles(); // remove all html files
            $this->generateHtmlFiles();
        } catch (Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>Html files generated successfully.</info>');

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    protected function validateConfigurations()
    {
        if (empty($this->config['available_languages'])) {
            throw new Exception(
                "The configuration key 'available_languages' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->availableLanguages = $this->config['available_languages'];
        if (empty($this->config['root_path'])) {
            throw new Exception(
                "The configuration key 'root_path' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->rootPath = '/'.ltrim(rtrim($this->config['root_path'], '/'), '/');
        if (empty($this->config['http_prefix'])) {
            throw new Exception(
                "The configuration key 'http_prefix' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->httpPrefix = $this->config['http_prefix'];
        if (empty($this->config['base_url'])) {
            throw new Exception(
                "The configuration key 'base_url' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->baseUrl = ltrim(rtrim($this->config['base_url'], '/'), '/');
        if (empty($this->config['html_path'])) {
            throw new Exception(
                "The configuration key 'html_path' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->htmlPath = $this->rootPath.'/'.ltrim(rtrim($this->config['html_path'], '/'), '/').'/';
        if (empty($this->config['images_folder'])) {
            throw new Exception(
                "The configuration key 'images_folder' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->imagesFolder = '/'.ltrim(rtrim($this->config['images_folder'], '/'), '/').'/';
    }

    protected function removeHtmlFiles()
    {
        $files = array();
        $it = new RecursiveDirectoryIterator($this->htmlPath);
        foreach (new RecursiveIteratorIterator($it) as $splFileInfo) {
            $file = $splFileInfo->getPathName();
            $parts = pathinfo($file);
            $extension = strtolower($parts['extension']);
            if ($extension == 'html' && file_exists($file)) {
                unlink($file);
            }
        }
    }

    protected function generateHtmlFiles()
    {
        if ($this->config['build_sitemapxml']) {
            $this->siteMapXml ='<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
            $this->siteMapXml.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:me="http://www.google.com/schemas/sitemap-me/1.0">'.PHP_EOL;
        }
        $parsedown = new MarkdownParser;
        $iterator = new RecursiveDirectoryIterator($this->htmlPath);
        foreach (new RecursiveIteratorIterator($iterator) as $splFileInfo) {

            $file = $splFileInfo->getPathName();
            preg_match("#/".preg_quote($this->config['route_folder'])."\/(.*?)/#", $file, $matches);
            $version = empty($matches[1]) ? null : $matches[1];
            $parts = pathinfo($file);
            $extension = strtolower($parts['extension']);

            if ($extension == 'md') {
                $text = file_get_contents($file);
                //
                // prepare docs for mysql fulltext search
                // 
                // https://stackoverflow.com/questions/12645839/how-can-i-remove-all-content-between-pre-tags-in-php
                // 
                // $searchBody = preg_replace('/<(tab)(?:(?!<\/\1).)*?<\/\1>/s', '', $text);
                $documentBody = strip_tags($text);
                $filename = $splFileInfo->getFilename();
                /**
                 * Generate site map xml
                 */
                if ($this->config['build_sitemapxml']) {
                    $this->buildSiteMapXml($splFileInfo, $version);    
                }
                /**
                 * Parse md content
                 */
                $html = $parsedown->text($text);
                /**
                 * Convert <blockquote></blockquote> to <div class="alert"></div>
                 */
                $html = $this->renderAlerts($html);
                /**
                 * Convert tab tags to html
                 */
                $html = $this->renderTabs($html, $parsedown);
                /**
                 * Add img responsive support ( class="img-fluid" )
                 */
                $html = $this->renderImages($html);
                /**
                 * Convert images to base64 
                 *
                 * https://stackoverflow.com/questions/11382530/searching-for-and-replacing-base64-image-string-with-php
                 * https://stackoverflow.com/questions/55305310/php-replace-image-src-and-add-a-new-attribute-in-image-tag-from-a-string-contain
                 */
                if ($this->config['base64_convert']) {
                    $html = preg_replace_callback(
                        '#(src="(.*?)")#', 
                        array($this, "renderImgToBase64"),
                        $html
                    );
                }
                $filePath = $parts['dirname'].DIRECTORY_SEPARATOR.$parts['filename'].'.html';
                file_put_contents($filePath, $html);
                chmod($filePath, 0644);
            }
        }
        if ($this->config['build_sitemapxml']) {
            $this->siteMapXml.= '</urlset>'.PHP_EOL;
            file_put_contents($this->rootPath.$this->config['xml_path'], $this->siteMapXml);    
        }
    }

    /**
     * Render images to base64 code
     * 
     * @param  array $matches matches
     * @return string
     */
    protected function renderImgToBase64($matches)
    {
        if (! empty($matches[2])) {
            $imgPath = $this->rootPath.'/'.$this->imagesFolder.$matches[2];
            $ext = pathinfo($imgPath, PATHINFO_EXTENSION);
            if (file_exists($imgPath) && in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $content = file_get_contents($imgPath);
                switch ($ext) {
                    case 'jpg':
                    $base64Src = 'data:image/jpg;base64, '.base64_encode($content);
                    case 'jpeg':
                    $base64Src = 'data:image/jpeg;base64, '.base64_encode($content);
                        break;
                    case 'gif':
                    $base64Src = 'data:image/gif;base64, '.base64_encode($content);
                        break;
                    case 'png':
                    $base64Src = 'data:image/png;base64, '.base64_encode($content);
                        break;
                }
                return 'src="'.$base64Src.'"';
            }
        }
        return $matches[1];
    }

    /**
     * Render images
     * 
     * @param  string $html string
     * @return string
     */
    protected function renderImages($html)
    {
        return preg_replace(
            '#<img([^>]+)>#', 
            '<img $1 class="img-fluid" />', 
            $html
        );
    }
    
    /**
     * Replace markdown blockquote tag to boostrap alerts
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderAlerts($html)
    {
        return preg_replace(
            '#<blockquote>\s<p>(.*?)<\/p>\s<\/blockquote>#',
            '<div class="alert alert-warning alert-dismissible fade show" role="alert">$1</div>',
            $html
        );
    }

    /**
     * Render boostrap tabs
     * 
     * @param  string $html      html
     * @param  object $parsedown ParseDown class
     * @return string
     */
    protected function renderTabs($html, $parsedown)
    {
        preg_match_all("#<tab>(.*?)<\/tab>#si", $html, $matches);
        $tabs = $matches[1];
        $i = 0;
        foreach ($tabs as $key => $value) {
            $output = "";
            $output.= '<div class="mb-5">'.PHP_EOL;
            $output.= '<div class="nav nav-tabs" role="tablist">'.PHP_EOL;
                foreach ($this->renderTabTitle($value) as $tt => $tabTitle) {
                    $active = ($tt == 0) ? 'active' : '';
                    $selected = ($active) ? 'true' : 'false';
                    $output.='<button class="nav-link '.$active.'" data-bs-toggle="tab" data-bs-target="#tabs-'.$key.$tt.'" type="button" role="tab" aria-controls="tabs-'.$key.$tt.'" aria-selected="'.$selected.'">';
                        $output.= str_replace(['<p>', '</p>'], '', $parsedown->text($tabTitle));
                    $output.='</button>'.PHP_EOL;
                }
            $output.='</div>'.PHP_EOL;

            $output.='<div class="tab-content">'.PHP_EOL;
                foreach ($this->renderTabContent($value) as $tc => $tabContent) {
                    $active = ($tc == 0) ? 'show active' : '';
                    $output.= '<div class="tab-pane fade '.$active.' p-3" id="tabs-'.$key.$tc.'" role="tabpanel" aria-labelledby="tabs-tab'.$key.$tc.'">';
                    $output.= $parsedown->text($tabContent);
                    $output.= '</div>'.PHP_EOL;
                }
            $output.= '</div>'.PHP_EOL;
            $output.="</div>";

            // Replace <tag></tag> tags with html
            $html = preg_replace(
                '#'.preg_quote($matches[0][$i], "<tcol>").'#',
                $output,
                $html
            );
            ++$i;
        }
        return $html;
    }

    /**
     * Render tab title
     * 
     * @param  string $value content
     * @return array
     */
    protected function renderTabTitle($value) : array
    {
        preg_match("#<title>(.*?)<\/title>#si", $value, $titles);
        $exp = explode("|", $titles[1]);
        return (array)$exp;
    }

    /**
     * Render tab content
     * 
     * @param  string $value content
     * @return array
     */
    protected function renderTabContent($value) : array
    {
        preg_match("#<content>(.*?)<\/content>#si", $value, $content);
        $exp = explode("<tcol>", $content[1]);
        return (array)$exp;
    }

    /**
     * Build site map xml
     * 
     * @param  SplFileInfo $splFileInfo object
     * @return void
     */
    protected function buildSiteMapXml($splFileInfo, string $version)
    {
        $file = $splFileInfo->getPathName();
        $filename = pathinfo($splFileInfo->getFilename(), PATHINFO_FILENAME);
        $folder = null;
        $subFolder = null;
        foreach ($this->availableLanguages as $langId) {
            $path = strstr($file, "/".$langId."/"); # /en/ui/resources.md
            if (is_string($path)) {
                $exp = explode("/", ltrim($path, "/"));
                if (count($exp) == 3) {
                    $locale = $exp[0]; // en
                    $folder = $exp[1]; // ui
                } else if (count($exp) == 4) {
                    $locale = $exp[0];
                    $folder = $exp[1];
                    $subFolder = $exp[2];
                }
                //
                // Sitemap Url
                // 
                if (empty($subFolder)) {
                    if (empty($folder)) {
                        $url = $this->httpPrefix.$this->baseUrl."/$version/$filename.html";
                    } else {
                        $url = $this->httpPrefix.$this->baseUrl."/$version/$folder/$filename.html";
                    }
                } else {
                    $url = $this->httpPrefix.$this->baseUrl."/$version/$folder/$subFolder/$filename.html";
                }
                $url = str_replace("{locale}", $langId, $url);
                $this->siteMapXml.= "\t<url>".PHP_EOL;
                $this->siteMapXml.= "\t\t<loc>$url</loc>".PHP_EOL;
                $this->siteMapXml.= "\t\t<changefreq>weekly</changefreq>".PHP_EOL;
                $this->siteMapXml.= "\t\t<priority>1</priority>".PHP_EOL;
                $this->siteMapXml.= "\t\t<lastmod>".date('c',time())."</lastmod>".PHP_EOL;
                $this->siteMapXml.= "\t</url>".PHP_EOL;
            }
        }
    }
}