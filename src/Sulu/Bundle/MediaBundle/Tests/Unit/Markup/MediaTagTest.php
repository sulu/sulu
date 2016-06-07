<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Markup;

use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Markup\MediaTag;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;

class MediaTagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineListBuilderFactory
     */
    private $listBuilderFactory;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var MediaTag
     */
    private $mediaTag;

    /**
     * @var ListBuilderInterface
     */
    private $listBuilder;

    /**
     * @var FieldDescriptorInterface[]
     */
    private $fieldDescriptors;

    protected function setUp()
    {
        $this->listBuilderFactory = $this->prophesize(DoctrineListBuilderFactory::class);
        $this->fieldDescriptorFactory = $this->prophesize(FieldDescriptorFactoryInterface::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);

        $this->mediaTag = new MediaTag(
            $this->listBuilderFactory->reveal(),
            $this->fieldDescriptorFactory->reveal(),
            $this->mediaManager->reveal()
        );

        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
        $this->listBuilderFactory->create('SuluMediaBundle:Media')
            ->shouldBeCalledTimes(1)->willReturn($this->listBuilder->reveal());

        $this->fieldDescriptors = $this->createFieldDescriptors();
        $this->fieldDescriptorFactory->getFieldDescriptorForClass(Media::class, ['locale' => 'de'])
            ->willReturn($this->fieldDescriptors);

        $this->listBuilder->setFieldDescriptors($this->fieldDescriptors)->shouldBeCalled();
        $this->listBuilder->addSelectField($this->fieldDescriptors['id'])->shouldBeCalled();
        $this->listBuilder->addSelectField($this->fieldDescriptors['version'])->shouldBeCalled();
        $this->listBuilder->addSelectField($this->fieldDescriptors['name'])->shouldBeCalled();
        $this->listBuilder->addSelectField($this->fieldDescriptors['title'])->shouldBeCalled();
    }

    /**
     * Returns field-descriptor for media.
     *
     * @return FieldDescriptorInterface[]
     */
    private function createFieldDescriptors()
    {
        return [
            'id' => $this->createFieldDescriptor('id'),
            'title' => $this->createFieldDescriptor('title'),
            'name' => $this->createFieldDescriptor('name'),
            'version' => $this->createFieldDescriptor('version'),
        ];
    }

    /**
     * Returns new field-descriptor for given field.
     *
     * @param string $field
     *
     * @return FieldDescriptorInterface
     */
    private function createFieldDescriptor($field)
    {
        return new DoctrineFieldDescriptor($field, $field, 'SuluMediaBundle:Media');
    }

    /**
     * Returns single media-item.
     *
     * @param int $id
     * @param string $title
     * @param string $name
     * @param string $version
     *
     * @return array
     */
    private function createMedia($id, $title, $name, $version)
    {
        return [
            'id' => $id,
            'title' => $title,
            'name' => $name,
            'version' => $version,
        ];
    }

    public function provideParseData()
    {
        return [
            [
                '<sulu:media id="1" title="Test-Title">Test-Content</sulu:media>',
                ['id' => '1', 'title' => 'Test-Title', 'content' => 'Test-Content'],
                '<a href="/test-url" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu:media id="1" title="Test-Title"/>',
                ['id' => '1', 'title' => 'Test-Title'],
                '<a href="/test-url" title="Test-Title">Media-Title</a>',
            ],
            [
                '<sulu:media id="1" title="Test-Title"></sulu:media>',
                ['id' => '1', 'title' => 'Test-Title'],
                '<a href="/test-url" title="Test-Title">Media-Title</a>',
            ],
            [
                '<sulu:media id="1">Test-Content</sulu:media>',
                ['id' => '1', 'content' => 'Test-Content'],
                '<a href="/test-url" title="Media-Title">Test-Content</a>',
            ],
            [
                '<sulu:media id="1"/>',
                ['id' => '1'],
                '<a href="/test-url" title="Media-Title">Media-Title</a>',
            ],
        ];
    }

    /**
     * @dataProvider provideParseData
     */
    public function testParseAll($tag, $attributes, $expected)
    {
        $media = $this->createMedia($attributes['id'], 'Media-Title', 'Media-Name', 1);

        $this->listBuilder->in($this->fieldDescriptors['id'], [$attributes['id']])->shouldBeCalled();
        $this->listBuilder->limit(1)->shouldBeCalled();
        $this->listBuilder->execute()->willReturn([$media]);
        $this->mediaManager->getUrl($media['id'], $media['name'], $media['version'])->willReturn('/test-url');

        $result = $this->mediaTag->parseAll([$tag => $attributes], 'de');

        $this->assertEquals([$tag => $expected], $result);
    }

    public function testParseAllMultipleTags()
    {
        $media1 = $this->createMedia(1, 'Media-Title-1', 'Media-Name-1', 1);
        $media2 = $this->createMedia(2, 'Media-Title-2', 'Media-Name-2', 1);

        $this->listBuilder->in($this->fieldDescriptors['id'], [1, 2])->shouldBeCalled();
        $this->listBuilder->limit(2)->shouldBeCalled();
        $this->listBuilder->execute()->willReturn([$media1, $media2]);

        $this->mediaManager->getUrl($media1['id'], $media1['name'], $media1['version'])->willReturn('/test-1');
        $this->mediaManager->getUrl($media2['id'], $media2['name'], $media2['version'])->willReturn('/test-2');

        $tag1 = '<sulu:media id="1">Test-Content</sulu:media>';
        $tag2 = '<sulu:media id="2" title="Test-Title"/>';
        $tag3 = '<sulu:media id="1" title="Test-Title">Test-Content</sulu:media>';

        $result = $this->mediaTag->parseAll(
            [
                $tag1 => ['id' => '1', 'content' => 'Test-Content'],
                $tag2 => ['id' => '2', 'title' => 'Test-Title'],
                $tag3 => ['id' => '1', 'title' => 'Test-Title', 'content' => 'Test-Content'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="/test-1" title="Media-Title-1">Test-Content</a>',
                $tag2 => '<a href="/test-2" title="Test-Title">Media-Title-2</a>',
                $tag3 => '<a href="/test-1" title="Test-Title">Test-Content</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleTagsMissingContent()
    {
        $this->listBuilder->in($this->fieldDescriptors['id'], [1])->shouldBeCalled();
        $this->listBuilder->limit(1)->shouldBeCalled();
        $this->listBuilder->execute()->willReturn([]);

        $tag1 = '<sulu:link id="1">Test-Content</sulu:link>';
        $tag2 = '<sulu:link id="1" title="Test-Title"/>';
        $tag3 = '<sulu:link id="1" title="Test-Title">Test-Content</sulu:link>';
        $tag4 = '<sulu:link id="1"/>';

        $result = $this->mediaTag->parseAll(
            [
                $tag1 => ['id' => '1', 'content' => 'Test-Content'],
                $tag2 => ['id' => '1', 'title' => 'Test-Title'],
                $tag3 => ['id' => '1', 'title' => 'Test-Title', 'content' => 'Test-Content'],
                $tag4 => ['id' => '1'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => 'Test-Content',
                $tag2 => 'Test-Title',
                $tag3 => 'Test-Content',
                $tag4 => '',
            ],
            $result
        );
    }

    public function testValidate()
    {
        $media = $this->createMedia(1, 'Media-Title', 'Media-Name', 1);
        $this->listBuilder->in($this->fieldDescriptors['id'], [1])->shouldBeCalled();
        $this->listBuilder->limit(1)->shouldBeCalled();
        $this->listBuilder->execute()->willReturn([$media]);

        $result = $this->mediaTag->validateAll(
            [
                '<sulu:media id="1" title="Test-Title">Test-Content</sulu:media>' => [
                    'id' => '1',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [],
            $result
        );
    }

    public function testValidateInvalid()
    {
        $this->listBuilder->in($this->fieldDescriptors['id'], [1])->shouldBeCalled();
        $this->listBuilder->limit(1)->shouldBeCalled();
        $this->listBuilder->execute()->willReturn([]);

        $result = $this->mediaTag->validateAll(
            [
                '<sulu:media id="1" title="Test-Title">Test-Content</sulu:media>' => [
                    'id' => '1',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            ['<sulu:media id="1" title="Test-Title">Test-Content</sulu:media>' => MediaTag::VALIDATE_REMOVED],
            $result
        );
    }

    public function testValidateMixed()
    {
        $media = $this->createMedia(1, 'Media-Title', 'Media-Name', 1);
        $this->listBuilder->in($this->fieldDescriptors['id'], [1, 2])->shouldBeCalled();
        $this->listBuilder->limit(2)->shouldBeCalled();
        $this->listBuilder->execute()->willReturn([$media]);

        $result = $this->mediaTag->validateAll(
            [
                '<sulu:media id="1" title="Test-Title">Test-Content</sulu:media>' => [
                    'id' => '1',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
                '<sulu:media id="2" title="Test-Title">Test-Content</sulu:media>' => [
                    'id' => '2',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                '<sulu:media id="2" title="Test-Title">Test-Content</sulu:media>' => MediaTag::VALIDATE_REMOVED,
            ],
            $result
        );
    }
}
