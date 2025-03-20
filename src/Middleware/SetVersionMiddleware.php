<?php

declare(strict_types=1);

namespace Olodoc\Middleware;

use Mezzio\Router\RouteResult;
use Olodoc\DocumentManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\I18n\Translator\TranslatorInterface;

class SetVersionMiddleware implements MiddlewareInterface
{
    public function __construct(private DocumentManagerInterface $documentManager)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $routeResult = $request->getAttribute(RouteResult::class);
        $routeParams = $routeResult->getMatchedParams();

        if (! empty($routeParams['version'])) {
            $this->documentManager->setVersion($routeParams['version']);
        }
        if (! empty($queryParams['v'])) {
            $this->documentManager->setVersion($queryParams['v']);
        }
        return $handler->handle($request);
    }
}
