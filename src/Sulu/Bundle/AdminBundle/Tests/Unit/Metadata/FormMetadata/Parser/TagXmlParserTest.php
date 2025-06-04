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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TagXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;

class TagXmlParserTest extends TestCase
{
    private TagXmlParser $tagXmlParser;

    protected function setUp(): void
    {
        $this->tagXmlParser = new TagXmlParser();
    }

    public function testParse(): void
    {
        $resource = $this->getTemplatesDirectory() . 'default.xml';
        $xpath = $this->loadXmlFile($resource);

        $tagNodes = $xpath->query('/x:template/x:tag') ?: [];

        $tagMetadatas = $this->tagXmlParser->load($xpath, $tagNodes);

        $this->assertSame([
            [
                'name' => 'test',
                'priority' => null,
                'attributes' => [
                    'value' => 'test-value',
                ],
            ],
            [
                'name' => 'test2',
                'priority' => null,
                'attributes' => [
                    'test' => 'test-value2',
                ],
            ],
            [
                'name' => 'test3',
                'priority' => null,
                'attributes' => [
                    'value' => 'test-value',
                ],
            ],
        ], \array_map(function(TagMetadata $tagMetadata) {
            return [
                'name' => $tagMetadata->getName(),
                'priority' => $tagMetadata->getPriority(),
                'attributes' => $tagMetadata->getAttributes(),
            ];
        }, $tagMetadatas));
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
