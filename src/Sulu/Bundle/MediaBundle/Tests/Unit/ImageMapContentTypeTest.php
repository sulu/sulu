<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Content\Types\ImageMapContentType;
use Sulu\Bundle\MediaBundle\Content\Types\SingleMediaSelection;
use Sulu\Component\Content\Compat\Block\BlockPropertyWrapper;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyType;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Subscriber\PHPCR\SuluNode;
use Sulu\Component\Content\Types\TextLine;

class ImageMapContentTypeTest extends TestCase
{
    /**
     * @var ImageMapContentType
     */
    private $imageMapContentType;

    /**
     * @var ObjectProphecy<TextLine>
     */
    private $textLineContentType;

    /**
     * @var ObjectProphecy<SingleMediaSelection>
     */
    private $singleMediaSelectionContentType;

    /**
     * @var ObjectProphecy<ContentTypeManagerInterface>
     */
    private $contentTypeManager;

    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var mixed[]
     */
    private $value;

    /**
     * @var mixed[]
     */
    private $types;

    protected function setUp(): void
    {
        $this->textLineContentType = $this->prophesize(TextLine::class);
        $this->singleMediaSelectionContentType = $this->prophesize(SingleMediaSelection::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);

        $this->contentTypeManager->get('text_line')->willReturn($this->textLineContentType);
        $this->contentTypeManager->get('single_media_selection')->willReturn($this->singleMediaSelectionContentType);

        $this->imageMapContentType = new ImageMapContentType($this->contentTypeManager->reveal());

        $this->types = [
            'headline' => [
                'children' => [
                    'headline' => 'text_line',
                ],
            ],
            'text' => [
                'children' => [
                    'text' => 'text_line',
                ],
            ],
        ];

        $this->value = [
            'imageId' => 1,
            'hotspots' => [
                [
                    'type' => 'text',
                    'text' => 'foo',
                    'hotspot' => [
                        'type' => 'circle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'radius' => 0.5,
                    ],
                ],
                [
                    'type' => 'headline',
                    'headline' => 'bar',
                    'hotspot' => [
                        'type' => 'rectangle',
                        'left' => 0.3,
                        'top' => 0.4,
                        'width' => 0.5,
                        'height' => 0.6,
                    ],
                ],
            ],
        ];

        $this->property = new Property(
            'imageMap',
            '',
            'image_map',
            false,
            true,
            1,
            1,
            [],
            [],
            null,
            'text'
        );

        foreach ($this->types as $key => $config) {
            $type = new PropertyType($key, []);

            foreach ($config['children'] as $childName => $childType) {
                $type->addChild(new Property($childName, '', $childType));
            }

            $this->property->addType($type);
        }
    }

    public function testRead(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $types = $this->types;
        $value = $this->value;
        $property = $this->property;

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($arg) use ($property) {
                return $arg instanceof BlockPropertyWrapper
                    && 'imageId' === $arg->getProperty()->getName()
                    && $arg->getBlock() === $property
                    && $arg->getName() === $arg->getBlock()->getName() . '-imageId';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($args) use ($value) {
            [$node, $property] = $args;
            $property->setValue($value['imageId']);
        })->shouldBeCalled();

        $this->textLineContentType->read(
            $node->reveal(),
            Argument::that(function($arg) use ($property) {
                return $arg instanceof BlockPropertyWrapper
                    && 'length' === $arg->getProperty()->getName()
                    && $arg->getBlock() === $property
                    && $arg->getName() === $arg->getBlock()->getName() . '-length';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->will(function($args) use ($value) {
            [$node, $property] = $args;
            $property->setValue(\count($value['hotspots']));
        })->shouldBeCalled();

        for ($i = 0; $i < \count($value['hotspots']); ++$i) {
            $this->textLineContentType->read(
                $node->reveal(),
                Argument::that(function($arg) use ($property, $i) {
                    return $arg instanceof BlockPropertyWrapper
                        && 'type' === $arg->getProperty()->getName()
                        && $arg->getBlock() === $property
                        && $arg->getName() === $arg->getBlock()->getName() . '-type#' . $i;
                }),
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->will(function($args) use ($value, $i) {
                [$node, $property] = $args;
                $property->setValue($value['hotspots'][$i]['type']);
            })->shouldBeCalled();

            $this->textLineContentType->read(
                $node->reveal(),
                Argument::that(function($arg) use ($property, $i) {
                    return $arg instanceof BlockPropertyWrapper
                        && 'hotspot' === $arg->getProperty()->getName()
                        && $arg->getBlock() === $property
                        && $arg->getName() === $arg->getBlock()->getName() . '-hotspot#' . $i;
                }),
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->will(function($args) use ($value, $i) {
                [$node, $property] = $args;
                $property->setValue(\json_encode($value['hotspots'][$i]['hotspot']));
            })->shouldBeCalled();

            $propertyType = $types[$value['hotspots'][$i]['type']];
            foreach ($propertyType['children'] as $childName => $childType) {
                $this->textLineContentType->read(
                    $node->reveal(),
                    Argument::that(function($arg) use ($property, $childName, $i) {
                        return $arg instanceof BlockPropertyWrapper
                            && $arg->getProperty()->getName() === $childName
                            && $arg->getBlock() === $property
                            && $arg->getName() === $arg->getBlock()->getName() . '-' . $childName . '#' . $i;
                    }),
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                )->will(function($args) use ($value, $i, $childName) {
                    [$node, $property] = $args;
                    $property->setValue($value['hotspots'][$i][$childName]);
                })->shouldBeCalled();
            }
        }

        $this->imageMapContentType->read($node->reveal(), $property, $webspaceKey, $languageCode, $segmentKey);

        $this->assertEquals($value, $property->getValue());
    }

    public function testHasValue(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $property = $this->property;

        $this->textLineContentType->hasValue(
            $node->reveal(),
            Argument::that(function($arg) use ($property) {
                return $arg instanceof BlockPropertyWrapper
                    && 'imageId' === $arg->getProperty()->getName()
                    && $arg->getBlock() === $property
                    && $arg->getName() === $arg->getBlock()->getName() . '-imageId';
            }),
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->willReturn(true);

        $this->assertTrue(
            $this->imageMapContentType->hasValue(
                $node->reveal(),
                $property,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )
        );
    }

    public function testWrite(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $userId = 1;
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $value = $this->value;
        $types = $this->types;
        $property = $this->property;

        $property->setValue($value);

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($arg) use ($property, $value) {
                return $arg instanceof BlockPropertyWrapper
                    && 'imageId' === $arg->getProperty()->getName()
                    && $arg->getBlock() === $property
                    && $arg->getName() === $arg->getBlock()->getName() . '-imageId'
                    && $arg->getValue() === $value['imageId'];
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->write(
            Argument::type(SuluNode::class),
            Argument::that(function($arg) use ($property, $value) {
                return $arg instanceof BlockPropertyWrapper
                    && 'length' === $arg->getProperty()->getName()
                    && $arg->getBlock() === $property
                    && $arg->getName() === $arg->getBlock()->getName() . '-length'
                    && $arg->getValue() === \count($value['hotspots']);
            }),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        for ($i = 0; $i < \count($value['hotspots']); ++$i) {
            $this->textLineContentType->write(
                Argument::type(SuluNode::class),
                Argument::that(function($arg) use ($property, $value, $i) {
                    return $arg instanceof BlockPropertyWrapper
                        && 'type' === $arg->getProperty()->getName()
                        && $arg->getBlock() === $property
                        && $arg->getName() === $arg->getBlock()->getName() . '-type#' . $i
                        && $arg->getValue() === $value['hotspots'][$i]['type'];
                }),
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();

            $this->textLineContentType->write(
                Argument::type(SuluNode::class),
                Argument::that(function($arg) use ($property, $value, $i) {
                    return $arg instanceof BlockPropertyWrapper
                        && 'hotspot' === $arg->getProperty()->getName()
                        && $arg->getBlock() === $property
                        && $arg->getName() === $arg->getBlock()->getName() . '-hotspot#' . $i
                        && $arg->getValue() === \json_encode($value['hotspots'][$i]['hotspot']);
                }),
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();

            $propertyType = $types[$value['hotspots'][$i]['type']];
            foreach ($propertyType['children'] as $childName => $childType) {
                $this->textLineContentType->write(
                    Argument::type(SuluNode::class),
                    Argument::that(function($arg) use ($property, $value, $childName, $i) {
                        return $arg instanceof BlockPropertyWrapper
                            && $arg->getProperty()->getName() === $childName
                            && $arg->getBlock() === $property
                            && $arg->getName() === $arg->getBlock()->getName() . '-' . $childName . '#' . $i
                            && $arg->getValue() === $value['hotspots'][$i][$childName];
                    }),
                    $userId,
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                )->shouldBeCalled();
            }
        }

        $this->imageMapContentType->write(
            $node->reveal(),
            $property,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

    public function testRemove(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $property = $this->property;

        $nodeProperty1 = $this->prophesize(\PHPCR\PropertyInterface::class);
        $nodeProperty1->getName()->willReturn('property1');
        $node->getProperty('property1')->willReturn($nodeProperty1->reveal());

        $nodeProperty2 = $this->prophesize(\PHPCR\PropertyInterface::class);
        $nodeProperty2->getName()->willReturn('property2');
        $node->getProperty('property2')->willReturn($nodeProperty2->reveal());

        $node->getProperties($property->getName() . '-*')->willReturn([
            $nodeProperty1->reveal(),
            $nodeProperty2->reveal(),
        ]);

        $nodeProperty1->remove()->shouldBeCalled();
        $nodeProperty2->remove()->shouldBeCalled();

        $this->imageMapContentType->remove($node->reveal(), $property, $webspaceKey, $languageCode, $segmentKey);
    }

    public function testImportData(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $userId = 1;
        $webspaceKey = 'example';
        $languageCode = 'en';
        $segmentKey = 's';

        $value = $this->value;
        $types = $this->types;
        $property = $this->property;

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($arg) use ($property, $value) {
                return $arg instanceof BlockPropertyWrapper
                    && 'imageId' === $arg->getProperty()->getName()
                    && $arg->getBlock() === $property
                    && $arg->getName() === $arg->getBlock()->getName() . '-imageId'
                    && $arg->getValue() === $value['imageId'];
            }),
            $value['imageId'],
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        $this->textLineContentType->importData(
            Argument::type(SuluNode::class),
            Argument::that(function($arg) use ($property, $value) {
                return $arg instanceof BlockPropertyWrapper
                    && 'length' === $arg->getProperty()->getName()
                    && $arg->getBlock() === $property
                    && $arg->getName() === $arg->getBlock()->getName() . '-length'
                    && $arg->getValue() === \count($value['hotspots']);
            }),
            \count($value['hotspots']),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )->shouldBeCalled();

        for ($i = 0; $i < \count($value['hotspots']); ++$i) {
            $this->textLineContentType->importData(
                Argument::type(SuluNode::class),
                Argument::that(function($arg) use ($property, $value, $i) {
                    return $arg instanceof BlockPropertyWrapper
                        && 'type' === $arg->getProperty()->getName()
                        && $arg->getBlock() === $property
                        && $arg->getName() === $arg->getBlock()->getName() . '-type#' . $i
                        && $arg->getValue() === $value['hotspots'][$i]['type'];
                }),
                $value['hotspots'][$i]['type'],
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();

            $this->textLineContentType->importData(
                Argument::type(SuluNode::class),
                Argument::that(function($arg) use ($property, $value, $i) {
                    return $arg instanceof BlockPropertyWrapper
                        && 'hotspot' === $arg->getProperty()->getName()
                        && $arg->getBlock() === $property
                        && $arg->getName() === $arg->getBlock()->getName() . '-hotspot#' . $i
                        && $arg->getValue() === \json_encode($value['hotspots'][$i]['hotspot']);
                }),
                \json_encode($value['hotspots'][$i]['hotspot']),
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            )->shouldBeCalled();

            $propertyType = $types[$value['hotspots'][$i]['type']];
            foreach ($propertyType['children'] as $childName => $childType) {
                $this->textLineContentType->importData(
                    Argument::type(SuluNode::class),
                    Argument::that(function($arg) use ($property, $value, $childName, $i) {
                        return $arg instanceof BlockPropertyWrapper
                            && $arg->getProperty()->getName() === $childName
                            && $arg->getBlock() === $property
                            && $arg->getName() === $arg->getBlock()->getName() . '-' . $childName . '#' . $i
                            && $arg->getValue() === $value['hotspots'][$i][$childName];
                    }),
                    $value['hotspots'][$i][$childName],
                    $userId,
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                )->shouldBeCalled();
            }
        }

        $this->imageMapContentType->importData(
            $node->reveal(),
            $property,
            $value,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

    public function testExportData(): void
    {
        $this->assertSame('foo', $this->imageMapContentType->exportData('foo'));
    }

    public function testGetContentData(): void
    {
        $types = $this->types;
        $value = $this->value;
        $property = $this->property;

        $image = $this->prophesize(Media::class);

        $this->singleMediaSelectionContentType->getContentData(
            Argument::that(function($arg) use ($value) {
                return $arg instanceof Property
                    && 'image' === $arg->getName()
                    && $arg->getValue() === ['id' => $value['imageId']];
            })
        )->willReturn($image->reveal())->shouldBeCalled();

        for ($i = 0; $i < \count($value['hotspots']); ++$i) {
            $propertyType = $types[$value['hotspots'][$i]['type']];
            foreach ($propertyType['children'] as $childName => $childType) {
                $this->textLineContentType->getContentData(
                    Argument::that(function($arg) use ($childName, $i, $value) {
                        return $arg instanceof Property
                            && $arg->getName() === $childName
                            && $arg->getValue() === $value['hotspots'][$i][$childName];
                    })
                )->will(function($args) {
                    return $args[0]->getValue();
                })->shouldBeCalled();
            }
        }

        $expectedContentData = [
            'image' => $image->reveal(),
            'hotspots' => $value['hotspots'],
        ];

        $property->setValue($value);

        $this->assertEquals(
            $expectedContentData,
            $this->imageMapContentType->getContentData($property)
        );
    }

    public function testGetViewData(): void
    {
        $types = $this->types;
        $value = $this->value;
        $property = $this->property;

        $this->singleMediaSelectionContentType->getViewData(
            Argument::that(function($arg) use ($value) {
                return $arg instanceof Property
                    && 'image' === $arg->getName()
                    && $arg->getValue() === ['id' => $value['imageId']];
            })
        )->willReturn([])->shouldBeCalled();

        for ($i = 0; $i < \count($value['hotspots']); ++$i) {
            $propertyType = $types[$value['hotspots'][$i]['type']];
            foreach ($propertyType['children'] as $childName => $childType) {
                $this->textLineContentType->getViewData(
                    Argument::that(function($arg) use ($childName, $i, $value) {
                        return $arg instanceof Property
                            && $arg->getName() === $childName
                            && $arg->getValue() === $value['hotspots'][$i][$childName];
                    })
                )->willReturn([])->shouldBeCalled();
            }
        }

        $expectedContentData = [
            'image' => [],
            'hotspots' => \array_map(function($hotspot) {
                return \array_map(
                    function() {
                        return [];
                    },
                    \array_filter(
                        $hotspot,
                        function($key) {
                            return 'type' !== $key && 'hotspot' !== $key;
                        },
                        \ARRAY_FILTER_USE_KEY
                    )
                );
            }, $value['hotspots']),
        ];

        $property->setValue($value);

        $this->assertEquals(
            $expectedContentData,
            $this->imageMapContentType->getViewData($property)
        );
    }

    public function testPreResolve(): void
    {
        $value = $this->value;
        $property = $this->property;

        $this->singleMediaSelectionContentType->preResolve(
            Argument::that(function($arg) use ($value) {
                return $arg instanceof Property
                    && 'image' === $arg->getName()
                    && $arg->getValue() === ['id' => $value['imageId']];
            })
        )->shouldBeCalled();

        $property->setValue($value);
        $this->imageMapContentType->preResolve($property);
    }
}
