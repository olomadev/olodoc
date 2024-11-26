<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use DOMXPath;
use DOMDocument;
use Olodoc\DocumentManagerInterface;
use Olodoc\Exception\ConfigurationErrorException;

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
     * Head tags
     * 
     * @var array
     */
    protected $hTags = array();

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
     * Parse anchor tags
     *
     * @param  string $htmlBody markdown content
     * @return array
     */
    public function parse(string $htmlBody) : array
    {
        $documentManager = $this->documentManager;
        $this->data = $this->parseHeadTags($htmlBody);
        return $this->data;
    }

    /**
     * Generate anchor links with <li> tags
     * 
     * @return void
     */
    public function generate()
    {
        if (! is_array($this->hTags)) {
            return;
        }
        if (count($this->hTags) == 0) {
            return;
        }
        $class = "";
        foreach ($this->hTags as $number => $v) {
            switch ($v['key']) {
                case 'h2':
                    $class = 'nav-sub-item-h2';
                    break;
                case 'h3':
                    $class = 'nav-sub-item-h3';
                    break;
                case 'h4':
                    $class = 'nav-sub-item-h4';
                    break;
                case 'h5':
                    $class = 'nav-sub-item-h5';
                    break;
                case 'h6':
                    $class = 'nav-sub-item-h6';
                    break;
                default:
                    $class = 'nav-sub-item-h0';
                    break;
            }
            if (! empty($v['value'])) {
                $this->anchors.= '<li class="nav-sub-item '.$class.'">';
                    $this->anchors.= '<a href="#'.$number.'-'.Self::formatName($v['value']).'" class="nav-sub-link">';
                    $this->anchors.= $v['value'];
                    $this->anchors.= '</a>';
                $this->anchors.= '</li>';
            }
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
     * @param  string $htmlBody parsed markdown content
     * 
     * @return array
     */
    protected function parseHeadTags($htmlBody) : array
    {
        $anchorParseQuery = $this->documentManager->getAnchorParseQuery();        
        $html = "<!DOCTYPE html>
        <html>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
        <body>".$htmlBody."</body>
        </html>";
        $doc = new DOMDocument;
        $doc->loadHTML($html, LIBXML_NOERROR);
        $xpath = new DOMXPath($doc);  
        $heads = $xpath->query($anchorParseQuery);
        foreach ($heads as $tag) {
           if ($tag->parentNode->tagName == 'body') {
                $this->hTags[] = [
                    'key' => $tag->tagName,
                    'value' => $tag->nodeValue,
                ];
           }
        }
        //
        // Build a names for <h> tags
        //
        $patterns = array();
        $replacements = array();
        foreach ($this->hTags as $number => $val) {
            $search  = '<'.$val['key'].'>'.$val['value'];
            $replace = '<a class="anchor" name="'.$number.'-'.Self::formatName($val['value']).'"></a><'.$val['key'].'>'.$val['value'];
            $htmlBody = str_replace($search, $replace, $htmlBody);
        }
        return [
            'html' => $htmlBody,
            'hTags' => $this->hTags,
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
