<?php

namespace Olodoc\Generator;

/**
 * @author Oloma <support@oloma.dev>
 *
 * BreadCrumb Generator Interface
 */
interface BreadCrumbGeneratorInterface
{
    /**
     * Generate <ol> and <li> tags for breadcrumbs
     * 
     * @return void
     */
    public function generate();
}