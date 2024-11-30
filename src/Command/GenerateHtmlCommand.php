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
use Laminas\I18n\Translator\TranslatorInterface;

class GenerateHtmlCommand extends Command
{
    private $config;
    private $baseUrl;
    private $rootPath;
    private $htmlPath;
    private $httpPrefix;
    private $imagesFolder;
    private $currentLocale;
    private $configArray = array();
    private $availableLocales = array();

    public function __construct(
        array $config, 
        private TranslatorInterface $translator
    )
    {
        $this->configArray = $config;
        $this->translator = $translator;

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
        if (empty($this->config['available_locales'])) {
            throw new Exception(
                "The configuration key 'available_languages' cannot be empty in your 'olodoc' configuration."
            );
        }
        $this->availableLocales = array_keys($this->config['available_locales']);
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
        $this->baseUrl = $this->httpPrefix.ltrim(rtrim($this->config['base_url'], '/'), '/');
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
            preg_match("#(\d+\.)?(\d+\.)?(\*|\d+)#", $file, $matches);
            $version = null;
            if (! empty($matches[0])) {
                $version = $matches[0];
            }
            $this->translator->setLocale($this->config['default_locale']);
            foreach ($this->availableLocales as $langId) {
                if (false !== strpos($file, "/".$langId."/")) {
                    $this->translator->setLocale($langId);
                }
            }
            $parts = pathinfo($file);
            $extension = strtolower($parts['extension']);
            if ($extension == 'md') {
                //
                // Get file content
                //
                $text = file_get_contents($file);
                /**
                 * Generate site map xml content and base url
                 */
                $this->buildSiteMapXml($splFileInfo, $version);
                /**
                 * Parse md content
                 */
                $html = $parsedown->text($text);
                /**
                 * Render all links
                 */
                $html = $this->renderLinks($html, $version);
                /**
                 * Render code escapes
                 */
                $html = $this->renderEscapes($html);
                 /**
                 * Convert <table></table> to <div class="table-responsive"></div>
                 */
                $html = $this->renderTables($html);
                /**
                 * Convert <blockquote></blockquote> to <div class="alert"></div>
                 */
                $html = $this->renderBlockquotes($html);
                /**
                 * Replace github markdown custom alerts
                 */
                $html = $this->renderGithubNoteAlerts($html);
                $html = $this->renderGithubTipAlerts($html);
                $html = $this->renderGithubImportantAlerts($html);
                $html = $this->renderGithubWarningAlerts($html);
                $html = $this->renderGithubCautionAlerts($html);
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
     * Remder html <a href=""></a> links
     * 
     * @param  string $text    text
     * @param  string $version doc version
     * @return string
     */
    protected function renderLinks(string $text, $version)
    {
        $baseUrl = $this->baseUrl;

        // Let's make internal links to http(s) and host supported
        // 
        // Example:
        // 
        // Search  <a href="/routing-and-pages/index.html"></a>
        // Replace <a href="//en.example.com/doc/1.0/routing-and-pages/index.html"></a>

        $text = preg_replace_callback("#<a href=\"[^http|\#](.*?)\">#", function ($src) use ($baseUrl, $version) {
            return '<a href="'.$baseUrl.'/'.$version.'/'.$src[1].'">';
        }, $text);

        return $text;
    }

    /**
     * Render escapes
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderEscapes(string $html)
    {
        return str_replace("\`", "`", $html);
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
            '<img class="img-fluid"$1>', 
            $html
        );
    }

    /**
     * Render tables to responsive
     * 
     * @param  string $html string
     * @return string
     */
    protected function renderTables($html)
    {
        return preg_replace(
            '#<table([^>]+)>((.*?|\s)+?)<\/table>#', 
            '<div class="table-responsive"><table $1>$2</table></div>',
            $html
        );
    }

    /**
     * Render markdown blockquotes
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderBlockquotes($html)
    {
        return preg_replace(
            '#<blockquote>(.*?)<\/blockquote>#',
            '<blockquote class="blockquote">$1</blockquote>',
            $html
        );
    }

    /**
     * Render github markdown [!NOTE] alert tags
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderGithubNoteAlerts($html)
    {
        return preg_replace(
            '#<blockquote>\s<p>\[\!NOTE\]((.*?|\s)+?)<\/blockquote>#',
            '<div class="ghd-alert ghd-alert-note"><p class="ghd-alert-title"><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M479.79-288q15.21 0 25.71-10.29t10.5-25.5q0-15.21-10.29-25.71t-25.5-10.5q-15.21 0-25.71 10.29t-10.5 25.5q0 15.21 10.29 25.71t25.5 10.5ZM444-432h72v-240h-72v240Zm36.28 336Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z"/></svg>'.$this->translator->translate("Note").'</p><p class="ghd-alert-body">$1</div>',
            $html
        );
    }

    /**
     * Render github markdown [!TIP] alert tags
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderGithubTipAlerts($html)
    {
        return preg_replace(
            '#<blockquote>\s<p>\[\!TIP\]((.*?|\s)+?)<\/blockquote>#',
            '<div class="ghd-alert ghd-alert-tip"><p class="ghd-alert-title"><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M479.79-96Q450-96 429-117.15T408-168h144q0 30-21.21 51t-51 21ZM336-216v-72h288v72H336Zm-15-120q-62-38-95.5-102.5T192-576q0-120 84-204t204-84q120 0 204 84t84 204q0 73-33.5 137.5T639-336H321Zm23-72h272q38-31 59-75t21-93q0-90.33-62.77-153.16-62.77-62.84-153-62.84Q390-792 327-729.16 264-666.33 264-576q0 49 21 93t59 75Zm136 0Z"/></svg>'.$this->translator->translate("Tip").'</p><p class="ghd-alert-body">$1</div>',
            $html
        );
    }

    /**
     * Render github markdown [!IMPORTANT] alert tags
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderGithubImportantAlerts($html)
    {
        return preg_replace(
            '#<blockquote>\s<p>\[\!IMPORTANT\]((.*?|\s)+?)<\/blockquote>#',
            '<div class="ghd-alert ghd-alert-important"><p class="ghd-alert-title"><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M479.79-360q15.21 0 25.71-10.29t10.5-25.5q0-15.21-10.29-25.71t-25.5-10.5q-15.21 0-25.71 10.29t-10.5 25.5q0 15.21 10.29 25.71t25.5 10.5ZM444-480h72v-264h-72v264ZM96-96v-696q0-29.7 21.15-50.85Q138.3-864 168-864h624q29.7 0 50.85 21.15Q864-821.7 864-792v480q0 29.7-21.15 50.85Q821.7-240 792-240H240L96-96Zm114-216h582v-480H168v522l42-42Zm-42 0v-480 480Z"/></svg>'.$this->translator->translate("Important").'</p><p class="ghd-alert-body">$1</div>',
            $html
        );
    }

    /**
     * Render github markdown [!WARNING] alert tags
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderGithubWarningAlerts($html)
    {
        return preg_replace(
            '#<blockquote>\s<p>\[\!WARNING\]((.*?|\s)+?)<\/blockquote>#',
            '<div class="ghd-alert ghd-alert-warning"><p class="ghd-alert-title"><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="m48-144 432-720 432 720H48Zm127-72h610L480-724 175-216Zm304.79-48q15.21 0 25.71-10.29t10.5-25.5q0-15.21-10.29-25.71t-25.5-10.5q-15.21 0-25.71 10.29t-10.5 25.5q0 15.21 10.29 25.71t25.5 10.5ZM444-384h72v-192h-72v192Zm36-86Z"/></svg>'.$this->translator->translate("Warning").'</p><p class="ghd-alert-body">$1</div>',
            $html
        );
    }

    /**
     * Render github markdown [!CAUTION] alert tags
     * 
     * @param  string $html html
     * @return string
     */
    protected function renderGithubCautionAlerts($html)
    {
        return preg_replace(
            '#<blockquote>\s<p>\[\!CAUTION\]((.*?|\s)+?)<\/blockquote>#',
            '<div class="ghd-alert ghd-alert-caution"><p class="ghd-alert-title"><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M214-384q0 46 19.5 87.5T288-223q-1-5-1.5-10t-.5-10q0-27 10-50.5t27-42.5l107-120 107 120q17 20 27 43.5t10 49.5q0 5-.5 10.5T572-222q35-29 54.5-71.5T646-384q0-51-17.5-100.5T578-576q-17 11-35.5 17t-40.5 6q-52 0-91.5-32T361-668q-36 33-63 67.5T252-530q-19 36-28.5 73t-9.5 73Zm216 36-53 60q-9 11-14 22.5t-5 25.5q0 30 21 51t51 21q30 0 51-21t21-51q0-14-5-26t-14-22l-53-60Zm0-468v119q0 30 21 51t51 21q17 0 31.5-7t24.5-20l16-20q64 38 104 118.5T718-384q0 120-84 204T430-96q-120 0-204-84t-84-204q0-112 76-226.5T430-816Zm398 312q-15 0-25.5-10.5T792-540q0-15 10.5-25.5T828-576q15 0 25.5 10.5T864-540q0 15-10.5 25.5T828-504Zm-36-120v-192h72v192h-72Z"/></svg>'.$this->translator->translate("Caution").'</p><p class="ghd-alert-body">$1</div>',
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
        if (empty($matches[1])) {
            return $html;
        }
        $tabs = $matches[1];
        $i = 0;
        foreach ($tabs as $key => $value) {
            $output = "";
            $output.= '<div class="tab-wrapper">'.PHP_EOL;
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
                    $output.= $parsedown->text(html_entity_decode($tabContent));
                    $output.= '</div>'.PHP_EOL;
                }
            $output.= '</div>'.PHP_EOL;
            $output.="</div>";
            //
            // Replace <tag></tag> tags with html
            // 
            $html = preg_replace(
                '#'.preg_quote($matches[0][$i], "<tab-column>").'#',
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
        preg_match("#<tab-title>(.*?)<\/tab-title>#si", $value, $titles);
        if (empty($titles[1])) {
            return array();
        }
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
        preg_match("#<tab-content>(.*?)<\/tab-content>#si", $value, $content);
        if (empty($content[1])) {
            return array();
        }
        preg_match_all("#<tab-column>((.*?|\s)+?)<\/tab-column>#si", $content[1], $matches, PREG_PATTERN_ORDER);
        return (array)$matches[1];
    }

    /**
     * Build site map xml
     * 
     * @param  SplFileInfo $splFileInfo object
     * @return void
     */
    protected function buildSiteMapXml($splFileInfo, $version)
    {
        $file = $splFileInfo->getPathName();
        $filename = pathinfo($splFileInfo->getFilename(), PATHINFO_FILENAME);
        $folder = null;
        $subFolder = null;
        foreach ($this->availableLocales as $langId) {
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
                $versionString = $version;
                if (empty($version)) {
                   $versionString = "/";
                } else {
                   $versionString = "/$version/";
                }
                if ($this->config['remove_default_locale'] && $this->config['default_locale'] == $langId) {
                    $this->baseUrl = str_replace(["{locale}.", "{locale}"], "", $this->baseUrl);
                } else {
                    $this->baseUrl = str_replace("{locale}", $langId, $this->baseUrl);
                }
                if (empty($subFolder)) {
                    if (empty($folder)) {
                        $url = $this->baseUrl.$versionString."$filename.html";
                    } else {
                        $url = $this->baseUrl.$versionString."$folder/$filename.html";
                    }
                } else {
                    $url = $this->baseUrl.$versionString."$folder/$subFolder/$filename.html";
                }
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