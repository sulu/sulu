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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\MetaXmlParser;
use Symfony\Contracts\Translation\TranslatorInterface;

class MetaXmlParserTest extends TestCase
{
    use ProphecyTrait;

    private MetaXmlParser $metaXmlParser;

    protected function setUp(): void
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $this->metaXmlParser = new MetaXmlParser(
            $translator->reveal(),
            ['en' => 'en', 'de' => 'de', 'fr' => 'fr', 'nl' => 'nl']
        );
    }

    public function testParse(): void
    {
        $resource = $this->getTemplatesDirectory() . 'default.xml';
        $xpath = $this->loadXmlFile($resource);
        $templateNode = ($xpath->query('/x:template') ?: null)?->item(0);
        \assert(null !== $templateNode, 'Expected <template> be defined for "' . $resource . '".');

        $meta = $this->metaXmlParser->load($xpath, $templateNode);

        $this->assertSame([
            'title' => [
                'de' => 'Tiers',
                'en' => 'Animals',
            ],
            'info_text' => [],
            'placeholder' => [],
        ], $meta);
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
