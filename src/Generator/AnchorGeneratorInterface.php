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
     * Parse anchor tags
     *
     * @param  string $htmlBody markdown content
     * @return array
     */
    public function parse(string $htmlBody) : array;

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