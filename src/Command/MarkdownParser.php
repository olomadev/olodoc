<?php

declare(strict_types=1);

namespace Olodoc\Command;

use ParsedownExtra;

class MarkdownParser extends ParsedownExtra {

    protected function blockFencedCode($Line)
    {
        if (preg_match('/^['.$Line['text'][0].']{3,}[ ]*([^`]+)?[ ]*$/', $Line['text'], $matches))
        {
            $Element = array(
                'name' => 'code',
                'text' => '',
            );
            
            $hlLines = '';
            $attributes = array();
            if (isset($matches[1]))
            {
                /**
                 * https://www.w3.org/TR/2011/WD-html5-20110525/elements.html#classes
                 * Every HTML element may have a class attribute specified.
                 * The attribute, if specified, must have a value that is a set
                 * of space-separated tokens representing the various classes
                 * that the element belongs to.
                 * [...]
                 * The space characters, for the purposes of this specification,
                 * are U+0020 SPACE, U+0009 CHARACTER TABULATION (tab),
                 * U+000A LINE FEED (LF), U+000C FORM FEED (FF), and
                 * U+000D CARRIAGE RETURN (CR).
                 */
                $language = substr($matches[1], 0, strcspn($matches[1], " \t\n\f\r"));

                $iniString = "";
                $iniArray = array();
                $exp = explode(" ", $matches[1]);

                // [line-highlight]\n data-line=1 [command-line]\n data-user=root\ndata-host=localhost\ndata-output=2, 4-8
                if (! empty($exp[1])) {
                    array_shift($exp);
                    $line = implode(" ", $exp);
                    $iniString = preg_replace('#(]\s+)#', "$1\n", $line);
                    $iniString = preg_replace('#("\s+)#', "$1\n", $iniString);
                    $iniArray = Self::parseIniString($iniString);
                }
                $class = 'language-'.$language;

                // prism.js plugin support for markdown
                // search for line highlight ..
                // 
                if (! empty($iniArray)) {
                    foreach ($iniArray as $key => $val) {
                        $class.= " ".trim($key);
                        foreach ($val as $attributeKey => $attributeVal) {
                            if ($attributeVal != "null") {
                                $attributes[$attributeKey] = trim($attributeVal);
                            }
                        }
                    }
                }

                $Element['attributes'] = array(
                    'class' => $class,
                );
            }

            $block = array(
                'char' => $Line['text'][0],
                'element' => array(
                    'name' => 'pre',
                    'attributes' => $attributes,  // ['data-line' => $hlLines]
                    'handler' => 'element',
                    'text' => $Element,
                ),
            );
            return $block;
        }
    }
    
    protected static function parseIniString($str)
    {   
        if (empty($str)) return false;

        $lines = explode("\n", $str);
        $ret = array();
        $insideSection = false;

        foreach ($lines as $line) {
           
            $line = trim($line);

            if (!$line || $line[0] == "#" || $line[0] == ";") continue;

            if ($line[0] == "[" && $endIdx = strpos($line, "]")){
                $insideSection = substr($line, 1, $endIdx-1);   
                // continue;
            }
            $tmp = explode("=", $line, 2);
            if (isset($tmp[0]) && $tmp[0][0] == "[") {
                $tmp[0] = str_replace(array('[', ']'), ["",""], $tmp[0]);
            }

            if ($insideSection) {

                $key = rtrim($tmp[0]);
                if (empty($tmp[1])) {
                    $value = "null";
                } else {
                    $value = ltrim($tmp[1]);    
                }
                if (preg_match("/^\".*\"$/", $value) || preg_match("/^'.*'$/", $value)) {
                    $value = mb_substr($value, 1, mb_strlen($value) - 2);
                }

                $t = preg_match("^\[(.*?)\]^", $key, $matches);
                if (!empty($matches) && isset($matches[0])) {

                    $arrName = preg_replace('#\[(.*?)\]#is', '', $key);

                    if(!isset($ret[$insideSection][$arrName]) || !is_array($ret[$insideSection][$arrName])) {
                        $ret[$insideSection][$arrName] = array();
                    }

                    if(isset($matches[1]) && !empty($matches[1])) {
                        $ret[$insideSection][$arrName][$matches[1]] = $value;
                    } else {
                        $ret[$insideSection][$arrName][] = $value;
                    }

                } else {
                    // var_dump($tmp);
                    $ret[$insideSection][trim($tmp[0])] = $value;
                }           

            } else {

                // $ret[trim($tmp[0])] = ltrim($tmp[1]);

            }
        }

        return $ret;
    }

}
