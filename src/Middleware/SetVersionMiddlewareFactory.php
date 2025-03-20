<?php

declare(strict_types=1);

namespace Olodoc\Middleware;

use Olodoc\DocumentManagerInterface;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SetVersionMiddlewareFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new SetVersionMiddleware(
            $container->get(DocumentManagerInterface::class),
        );
    }
}