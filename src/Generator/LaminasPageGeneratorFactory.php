<?php

declare(strict_types=1);

namespace Olodoc\Generator;

use Olodoc\DocumentManagerInterface;
use Psr\Container\ContainerInterface;

class LaminasPageGeneratorFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new PageGenerator(
            $container->get(DocumentManagerInterface::class)
        );
    }
}
