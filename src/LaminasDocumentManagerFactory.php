<?php

declare(strict_types=1);

namespace Olodoc;

use Psr\Container\ContainerInterface;

class LaminasDocumentManagerFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['olodoc'];

        $documentManager = new DocumentManager;
        $documentManager->setAvailableVersions($config['available_versions']);
        $documentManager->setDefaultVersion($config['default_version']);
        $documentManager->setDefaultLocale($config['default_locale']);
        $documentManager->setRootPath($config['root_path']);  // /var/www/olodoc-site
        $documentManager->setHttpPrefix($config['http_prefix']); // https(s)://
        $documentManager->setBaseUrl($config['base_url']); // docs
        $documentManager->setRemoveDefaultLocale($config['remove_default_locale']); // removes default locales from base uri(s)
        $documentManager->setHtmlPath($config['html_path']); // /data/docs/
        $documentManager->setXmlMapPath($config['xml_path']); // /public/sitemap.xml
        $documentManager->setBase64Convert($config['base64_convert']); // true
        $documentManager->setAnchorGenerations($config['anchor_generations']); // false
        $documentManager->setAnchorsForIndexPages($config['anchors_for_index_pages']); // false

        return $documentManager;
    }
}
