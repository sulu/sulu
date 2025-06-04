<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Loader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\MetaXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\PropertiesXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\SchemaXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TagXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Types\BlockContentType;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Translation\TranslatorInterface;

class TemplateXmlLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var TemplateXmlLoader
     */
    private $loader;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $tagXmlParser = new TagXmlParser();
        $metaXmlParser = new MetaXmlParser(
            $this->translator->reveal(),
            ['en' => 'en', 'de' => 'de', 'fr' => 'fr', 'nl' => 'nl']
        );
        $propertiesXmlParser = new PropertiesXmlParser(
            $tagXmlParser,
            $metaXmlParser,
        );
        $schemaXmlParser = new SchemaXmlParser();

        $container = new Container();
        $propertyMetadataMapperRegistry = new PropertyMetadataMapperRegistry($container);
        $schemaMetadataProvider = new SchemaMetadataProvider($propertyMetadataMapperRegistry);
        $blockMetadataProvider = new BlockContentType(
            $this->prophesize(ContentTypeManagerInterface::class)->reveal(),
            $schemaMetadataProvider,
            [],
        );
        $container->set('block', $blockMetadataProvider);

        $this->loader = new TemplateXmlLoader($propertiesXmlParser, $schemaXmlParser, $tagXmlParser, $metaXmlParser, $schemaMetadataProvider);
    }

    public function testLoadDefaultTemplate(): void
    {
        $formMetadata = $this->loader->load($this->getTemplatesDirectory() . 'default.xml');

        $this->assertSame([
            'titles' => [
                'de' => 'Tiers',
                'en' => 'Animals',
            ],
            'tags' => [
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
            ],
            'schema' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'blocks' => [
                                'type' => 'array',
                                'items' => [
                                    'allOf' => [
                                        [
                                            'if' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'type' => [
                                                        'const' => 'text_block',
                                                    ],
                                                ],
                                                'required' => [
                                                    'type',
                                                ],
                                            ],
                                            'then' => [
                                                '$ref' => '#/definitions/text_block',
                                            ],
                                        ],
                                        [
                                            'if' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'type' => [
                                                        'const' => 'text_block2',
                                                    ],
                                                ],
                                                'required' => [
                                                    'type',
                                                ],
                                            ],
                                            'then' => [
                                                '$ref' => '#/definitions/text_block2',
                                            ],
                                        ],
                                        [
                                            'if' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'type' => [
                                                        'const' => 'editor',
                                                    ],
                                                ],
                                                'required' => [
                                                    'type',
                                                ],
                                            ],
                                            'then' => [
                                                'type' => [
                                                    'number',
                                                    'string',
                                                    'boolean',
                                                    'object',
                                                    'array',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                        [
                                            'if' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'type' => [
                                                        'const' => 'block',
                                                    ],
                                                ],
                                                'required' => [
                                                    'type',
                                                ],
                                            ],
                                            'then' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'blocks' => [
                                                        'type' => 'array',
                                                        'items' => [
                                                            'allOf' => [
                                                                [
                                                                    'if' => [
                                                                        'type' => 'object',
                                                                        'properties' => [
                                                                            'type' => [
                                                                                'const' => 'text_block2',
                                                                            ],
                                                                        ],
                                                                        'required' => [
                                                                            'type',
                                                                        ],
                                                                    ],
                                                                    'then' => [
                                                                        '$ref' => '#/definitions/text_block2',
                                                                    ],
                                                                ],
                                                                [
                                                                    'if' => [
                                                                        'type' => 'object',
                                                                        'properties' => [
                                                                            'type' => [
                                                                                'const' => 'text_block3',
                                                                            ],
                                                                        ],
                                                                        'required' => [
                                                                            'type',
                                                                        ],
                                                                    ],
                                                                    'then' => [
                                                                        '$ref' => '#/definitions/text_block3',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'required' => [
                            'title',
                            'url',
                        ],
                    ],
                    [
                        'anyOf' => [
                            [
                                'type' => 'object',
                                'required' => [
                                    'blog',
                                ],
                            ],
                            [
                                'type' => 'object',
                                'required' => [
                                    'localized_blog',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $this->metadataToArray($formMetadata));
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataToArray(FormMetadata $formMetadata): array
    {
        return [
            'titles' => [
                'de' => 'Tiers',
                'en' => 'Animals',
            ],
            'tags' => \array_map(function(TagMetadata $tagMetadata) {
                return [
                    'name' => $tagMetadata->getName(),
                    'priority' => $tagMetadata->getPriority(),
                    'attributes' => $tagMetadata->getAttributes(),
                ];
            }, $formMetadata->getTags()),
            'schema' => $formMetadata->getSchema()->toJsonSchema(),
        ];
    }

    private function getTemplatesDirectory(): string
    {
        return \dirname(__DIR__, 4) . \DIRECTORY_SEPARATOR
            . 'Application' . \DIRECTORY_SEPARATOR . 'config' . \DIRECTORY_SEPARATOR . 'templates' . \DIRECTORY_SEPARATOR . 'pages' . \DIRECTORY_SEPARATOR;
    }
}
