<?php

declare(strict_types=1);

namespace Olodoc\Loader;

use Olodoc\DocumentManagerInterface;
use Olodoc\Exception\FileNotFoundException;

/**
 * @author Oloma <support@oloma.dev>
 *
 * File Loader
 *
 * Responsible for loading html files
 */
class FileLoader
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
     * Load documentation file
     * 
     * @param  string $file file path
     * @return string
     */
    public function loadFile(string $file) : string
    {
        $content = "";
        if (! file_exists($file)) {
            throw new FileNotFoundException(
                sprintf(
                    "File loader error: Documentation file %s not found.",
                    $file
                )
            );
        }
        $content = file_get_contents($file);
        return $content;
    }
}
