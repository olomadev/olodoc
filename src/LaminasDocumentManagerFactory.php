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
        $documentManager->setRootPath($config['root_path']);  // /var/www/olodoc-site
        $documentManager->setDocumentFolder($config['document_folder']); // docs
        $documentManager->setHtmlPath($config['html_path']); // /data/docs/
        $documentManager->setXmlMapPath($config['xml_path']); // /public/sitemap.xml
        $documentManager->setBase64Convert($config['base64_convert']); // true

        return $documentManager;
    }
}
