<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use Olodoc\DocumentManagerInterface;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Anchor Generator
 *
 * Responsible for creating links on the documentation page
 */
class AnchorGenerator implements AnchorGeneratorInterface
{
    /**
     * All links on the page
     * 
     * @var string
     */
    protected $anchors = "";
    
    /**
     * Parse data
     * 
     * @var array
     */
    protected $data = array();

    /**
     * Document manager
     * 
     * @var object
     */
    protected $documentManager;    

    /**
     * Constructor
     * 
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * Parse <a href=""></a> tags
     *
     * @param  string $html markdown html content
     *
     * @return string html
     */
    public function parse(string $html)
    {
        $documentManager = $this->documentManager;
        
        // Let's make internal links to http(s) and host supported
        // 
        // Example:
        // 
        // Search  <a href="/routing-and-pages/index.html"></a>
        // Replace <a href="//en.example.com/doc/1.0/routing-and-pages/index.html"></a>

        $html = preg_replace_callback("#<a href=\"[^http](.*?)\">#", function ($src) use ($documentManager) {
            return '<a href="'.$documentManager->getBaseUrl().$documentManager->getVersion().'/'.$src[1].'">';
        }, $html);
        $this->data = Self::parseHeadTags($html);

        return $this->data;
    }

    /**
     * Generate anchor links with <li> tags
     * 
     * @return void
     */
    public function generate()
    {
        if (! is_array($this->data['subItems'])) {
            return;
        }
        if (count($this->data['subItems']) == 0) {
            return;
        }
        foreach ($this->data['subItems'] as $k => $v) {
            switch ($this->data['subHeaders'][$k]) {
                case '2':
                    $style = 'padding-left:0px;font-weight:bold;';
                    break;
                case '3':
                    $style = 'padding-left:0px;';
                    break;
                case '4':
                    $style = 'padding-left:3px;';
                    break;
                case '5':
                    $style = 'padding-left:5px;';
                    break;
                case '6':
                    $style = 'padding-left:7px;';
                    break;
                default:
                    $style = 'padding-left:0px;';
                    break;
            }
            $this->anchors.= '<li class="nav-sub-item" style="'.$style.'"><a href="#'.Self::formatName($v).'" class="nav-sub-link">'.$v.'</a></li>';
        }
    }

    /**
     * Returns to anchor items
     *
     * @return string html <li>
     */
    public function getAnchorItems()
    {
        return $this->anchors;
    }

    /**
     * Parse header tags and build anchors for right menu
     *
     * @param  string $html parsed markdown content
     * 
     * @return array
     */
    protected static function parseHeadTags($html) : array
    {
        // Find <h> tags
        //
        $match = preg_match_all('#<h(2|3|4|5|6)>(.*)<\/h.*>#', $html, $matches);
        $subHeaders = ($match > 0) ? $matches[1] : array();
        $subItems = ($match > 0) ? $matches[2] : array();

        // Build a names for <h> tags
        //
        $patterns = array();
        $replacements = array();
        foreach ($subItems as $i => $item) {
            $n = $subHeaders[$i];
            $search  = '<h'.$n.'>'.$item;
            $replace = '<a class="anchor" name="'.Self::formatName($item).'"></a><h'.$n.'>'.$item;
            $html = str_replace($search, $replace, $html);
        }
        return [
            'html' => $html,
            'subItems' => $subItems,
            'subHeaders' => $subHeaders
        ];
    }
    
    /**
    * Normalize function names to display friendly on the right menu.
    *
    * e.g. setAlias(), setFactory()  => convert to setAlias, setFactory.
    */
    protected static function formatName($item)
    {
        return preg_replace('#\s+\(.*\)|\(.*\)#', '', trim($item));
    }

}
