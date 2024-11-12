<?php

namespace Olodoc\Generator;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Anchor Generator Interface
 */
interface AnchorGeneratorInterface
{
    /**
     * Parse <a href=""></a> tags
     *
     * @param  string $html markdown html content
     *
     * @return string html
     */
    public function parse(string $html);

    /**
     * Generate anchor links with <li> tags
     * 
     * @return void
     */
    public function generate();

    /**
     * Returns to anchor items
     *
     * @return string html <li>
     */
    public function getAnchorItems();

}