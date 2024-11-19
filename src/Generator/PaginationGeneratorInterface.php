<?php

namespace Olodoc\Generator;

/**
 * @author Oloma <support@oloma.dev>
 *
 * Pagination Generator Interface
 */
interface PaginationGeneratorInterface
{
    /**
     * Generates and returns to navigation bar html with footer
     * 
     * @return string
     */
    public function generate($prevPageLabel = "", $nextPageLabel = "") : string;
}