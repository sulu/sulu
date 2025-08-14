<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Parser;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TemplateXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;

class TemplateXmlParserTest extends TestCase
{
    private TemplateXmlParser $templateXmlParser;

    protected function setUp(): void
    {
        $this->templateXmlParser = new TemplateXmlParser();
    }

    public function testParseDefault(): void
    {
        $resource = $this->getTemplatesDirectory() . 'default.xml';
        $xpath = $this->loadXmlFile($resource);
        $templateNode = ($xpath->query('/x:template') ?: null)?->item(0);
        \assert(null !== $templateNode, 'Expected <template> be defined for "' . $resource . '".');

        $templateMetadata = $this->templateXmlParser->load($xpath, $templateNode);

        $this->assertSame([
            'controller' => 'Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction',
            'view' => 'pages/animals',
            'cacheLifetime' => [
                'type' => 'seconds',
                'value' => '2400',
            ],
        ], $this->templateMetadataToArray($templateMetadata));
    }

    public function testParseOverview(): void
    {
        $resource = $this->getTemplatesDirectory() . 'overview.xml';
        $xpath = $this->loadXmlFile($resource);
        $templateNode = ($xpath->query('/x:template') ?: null)?->item(0);
        \assert(null !== $templateNode, 'Expected <template> be defined for "' . $resource . '".');

        $templateMetadata = $this->templateXmlParser->load($xpath, $templateNode);

        $this->assertSame([
            'controller' => 'Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction',
            'view' => 'pages/overview',
            'cacheLifetime' => [
                'type' => 'expression',
                'value' => '0 2 * * *',
            ],
        ], $this->templateMetadataToArray($templateMetadata));
    }

    /**
     * @return array<string, mixed>
     */
    private function templateMetadataToArray(TemplateMetadata $templateMetadata): array
    {
        return [
            'controller' => $templateMetadata->getController(),
            'view' => $templateMetadata->getView(),
            'cacheLifetime' => $templateMetadata->getCacheLifetime() ? [
                'type' => $templateMetadata->getCacheLifetime()->getType(),
                'value' => $templateMetadata->getCacheLifetime()->getValue(),
            ] : null,
        ];
    }

    private function loadXmlFile(string $resource): \DOMXPath
    {
        $xmlDocument = new \DOMDocument();
        $xmlDocument->load($resource);

        $xpath = new \DOMXPath($xmlDocument);
        $xpath->registerNamespace('x', TemplateXmlLoader::SCHEMA_NAMESPACE_URI);

        return $xpath;
    }

    private function getTemplatesDirectory(): string
    {
        return \dirname(__DIR__, 4) . \DIRECTORY_SEPARATOR
            . 'Application' . \DIRECTORY_SEPARATOR . 'config' . \DIRECTORY_SEPARATOR . 'templates' . \DIRECTORY_SEPARATOR . 'pages' . \DIRECTORY_SEPARATOR;
    }
}
