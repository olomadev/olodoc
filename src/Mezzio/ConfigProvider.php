<?php

declare(strict_types=1);

namespace Olodoc\Mezzio;

use Olodoc\DocumentManagerInterface;
use Olodoc\LaminasDocumentManagerFactory;
use Olodoc\Generator\LaminasPageGeneratorFactory;
use Olodoc\Generator\PageGeneratorInterface;

/**
 * Configration provider for Mezzio
 */
class ConfigProvider
{
    /**
     * Returns to service mappings
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Returns to service mappings
     *
     * @return ServiceManagerConfiguration array
     */
    public function getDependencyConfig() : array
    {
        $dependencies = [
            'factories' => [
                DocumentManagerInterface::class => LaminasDocumentManagerFactory::class,
                PageGeneratorInterface::class => LaminasPageGeneratorFactory::class,
            ],
        ];

        return $dependencies;
    }

}
