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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TemplateXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\BlockPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
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
        $templateXmlParser = new TemplateXmlParser();

        $container = new Container();
        $propertyMetadataMapperRegistry = new PropertyMetadataMapperRegistry($container);
        $schemaMetadataProvider = new SchemaMetadataProvider($propertyMetadataMapperRegistry);
        $blockMetadataProvider = new BlockPropertyMetadataMapper(
            $schemaMetadataProvider,
        );
        $container->set('block', $blockMetadataProvider);

        $this->loader = new TemplateXmlLoader($propertiesXmlParser, $schemaXmlParser, $tagXmlParser, $metaXmlParser, $templateXmlParser, $schemaMetadataProvider);
    }

    public function testLoadDefaultTemplate(): void
    {
        $formMetadata = $this->loader->load($this->getTemplatesDirectory() . 'default.xml');

        $this->assertSame([
            'template' => [
                'controller' => 'Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction',
                'view' => 'pages/animals',
                'cacheLifetime' => [
                    'type' => 'seconds',
                    'value' => '2400',
                ],
            ],
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

    public function testLoadOverviewTemplate(): void
    {
        $formMetadata = $this->loader->load($this->getTemplatesDirectory() . 'overview.xml');

        $this->assertSame([
            'template' => [
                'controller' => 'Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction',
                'view' => 'pages/overview',
                'cacheLifetime' => [
                    'type' => 'expression',
                    'value' => '0 2 * * *',
                ],
            ],
            'titles' => [
                'de' => 'Tiers',
                'en' => 'Animals',
            ],
            'tags' => [
                [
                    'name' => 'test3',
                    'priority' => null,
                    'attributes' => [],
                ],
            ],
            'schema' => [
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
                                                'const' => 'editor',
                                            ],
                                        ],
                                        'required' => ['type'],
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
                            ],
                        ],
                    ],
                    'blocks_with_custom_settings_form_key' => [
                        'type' => 'array',
                        'items' => [
                            'allOf' => [
                                [
                                    'if' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'type' => [
                                                'const' => 'editor',
                                            ],
                                        ],
                                        'required' => ['type'],
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
                            ],
                        ],
                    ],
                ],
                'required' => [
                    'title',
                    'url',
                ],
            ],
        ], $this->metadataToArray($formMetadata));
    }

    public function testLoadSnippetTemplate(): void
    {
        $formMetadata = $this->loader->load($this->getTemplatesDirectory() . '../snippets/default.xml');

        $this->assertSame([
            'template' => [
                'controller' => null,
                'view' => null,
                'cacheLifetime' => null,
            ],
            'titles' => [
                'de' => 'Tiers',
                'en' => 'Animals',
            ],
            'tags' => [],
            'schema' => [
                'type' => 'object',
                'required' => [
                    0 => 'title',
                ],
            ],
        ], $this->metadataToArray($formMetadata));
    }

    public function testLoadBlockTemplate(): void
    {
        $formMetadata = $this->loader->load($this->getTemplatesDirectory() . '../blocks/text_block.xml');

        $this->assertSame([
            'template' => [
                'controller' => null,
                'view' => null,
                'cacheLifetime' => null,
            ],
            'titles' => [
                'de' => 'Tiers',
                'en' => 'Animals',
            ],
            'tags' => [],
            'schema' => [
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
                            ],
                        ],
                    ],
                ],
                'required' => [
                    'title',
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
            'template' => (function() use ($formMetadata) {
                $template = $formMetadata->getTemplate();
                if (null === $template) {
                    return null;
                }

                return [
                    'controller' => $template->getController(),
                    'view' => $template->getView(),
                    'cacheLifetime' => $template->getCacheLifetime() ? [
                        'type' => $template->getCacheLifetime()->getType(),
                        'value' => $template->getCacheLifetime()->getValue(),
                    ] : null,
                ];
            })(),
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
