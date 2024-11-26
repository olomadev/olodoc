<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use Olodoc\DocumentManagerInterface;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Js Generator
 *
 * Responsible for creating js codes on the documentation page
 */
class JsGenerator implements JsGeneratorInterface
{
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
     * Generates all js codes
     * 
     * @return string
     */
    public function generate()
    {
        $script = $this->getPaginationJs();
        $script.= $this->getSearchIconsJs();
        $script.= $this->getSearchResultsJs();
        $script.= $this->getSearchNoResultsJs();
        $script.= $this->getBoostrapTabPrismJs();
        $script.= $this->getLanguagesFlagJs();
        return $script;
    }

    /**
     * Returns to page navigation javascript
     * 
     * @return string
     */
    public function getPaginationJs() : string
    {
        $script = PHP_EOL."function olodocGoToPage(url) {
            window.location.href = url;
        }
        function olodocPrevPage(url) {
            window.location.href = '%s' + url;
        }
        function olodocNextPage(url) {
            window.location.href = '%s' + url;
        }
        function olodocChangeVersion() {
            var version = document.getElementById(\"version-combobox\").value
            window.location.href = '%s' + version + '/index.html'
        }".PHP_EOL;
        return sprintf(
            $script,
            $this->documentManager->getBaseUrl().$this->documentManager->getVersion(),
            $this->documentManager->getBaseUrl().$this->documentManager->getVersion(),
            $this->documentManager->getBaseUrl()
        );
    }

    /**
     * Returns to search icon javascript
     * 
     * @return string
     */
    public function getSearchIconsJs() : string
    {
        return PHP_EOL.'document.getElementById("cancel-icon").addEventListener("click", function(){
            document.getElementById("search-input").value = "";
            document.getElementById("cancel-icon").style.display = "none";
            document.getElementById("search-icon").style.display = "block";
            olodocNoSearchResultFound();
        })'.PHP_EOL;
    }

    /**
     * Returns to no search result javascript
     * 
     * @return string
     */
    public function getSearchNoResultsJs() : string
    {
        return PHP_EOL.'function olodocNoSearchResultFound() {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                var response = JSON.parse(this.responseText);
                var html = \'<div class="no-search-result">\';
                    html += \'<h3 class="no-search-result-title">\' + response.data.title + \'</h3>\';
                    html += \'<div class="no-search-result-item card">\';
                      html += \'<div class="card-body">\';
                        html += response.data.resultText;
                      html += \'</div>\';
                    html += \'</div>\';
                html += \'</div>\';
                document.getElementById("markdown-content").innerHTML = html;
              }
            }
            xmlhttp.open("GET","/search", true);
            xmlhttp.send();
        }'.PHP_EOL;
    }

    /**
     * Returns to search results javascript
     * 
     * @return string
     */
    public function getSearchResultsJs() : string
    {
        $script = PHP_EOL.'function olodocSearchResult(str) {
            if (str.length == 0) {
              document.getElementById("cancel-icon").style.display = "none";
              document.getElementById("search-icon").style.display = "block";
              return;
            }
            document.getElementById("cancel-icon").style.display = "block";
            document.getElementById("search-icon").style.display = "none";
            if (str.length < 3) {
              olodocNoSearchResultFound();
              return;
            }
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                var response = JSON.parse(this.responseText);
                if (response["data"] 
                    && response["data"]["results"] 
                    && Array.isArray(response["data"]["results"])
                    && response["data"]["results"].length > 0) {
                        var html = \'<div class="search-result">\';
                        html += \'<h3 class="search-result-title">\' + response.data.title + \'</h3>\';
                        html += \'<div class="search-result-text">\' + response.data.resultText + \'</div>\';
                        response["data"]["results"].forEach(function(item) {
                          var url = item.baseUrl + \'/\' + item.version + item.file;
                          html += \'<div class="search-result-item card">\';
                          html += \'<a href="\' + url + \'" class="search-result-item-link">\';
                              html += \'<blockquote style="margin-bottom:0;">\';
                              html += item.file;
                              html += \'<footer class="blockquote-footer">\' + item.line + \'</footer>\';
                              html += \'</blockquote>\';
                          html += \'</a>\';
                          html += \'</div>\';
                        });
                      html += \'</div>\';
                      document.getElementById("markdown-content").innerHTML = html;
                } else {
                  olodocNoSearchResultFound()
                } // end if
              } // end ready state
            } // end function
            xmlhttp.open("GET","/search?v=" + encodeURIComponent("%s") + "&q=" + str, true);
            xmlhttp.send();
        }'.PHP_EOL;
        return sprintf(
            $script,
            $this->documentManager->getBaseUrl()
        );
    }

    /**
     * Returns to bootstrap tab init script for prism js
     * 
     * @return string
     */
    public function getBoostrapTabPrismJs() : string
    {
        return PHP_EOL.'var bsTabEl = document.querySelectorAll(\'button[data-bs-toggle="tab"]\')
            bsTabEl.forEach(function(el){
            el.addEventListener("shown.bs.tab", function (e) {
              Prism.highlightAll(); // init prism for each tab
            })    
        })'.PHP_EOL;
    }

    /**
     * Returns to language mouseover javascript
     *
     * @return string
     */
    public function getLanguagesFlagJs()
    {
        return PHP_EOL.'function onMouseOverFlag(key){
            var svg = document.getElementById("flag-icons-" + key)
            svg.classList.remove("grayscale");
        }
        function onMouseOutFlag(key){
            var svg = document.getElementById("flag-icons-" + key)
            svg.classList.add("grayscale");
        }'.PHP_EOL;
    }

}

