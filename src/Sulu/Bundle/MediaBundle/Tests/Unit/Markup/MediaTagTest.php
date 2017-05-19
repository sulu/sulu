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

use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Markup\MediaTag;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

class MediaTagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var MediaTag
     */
    private $mediaTag;

    protected function setUp()
    {
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);

        $this->mediaTag = new MediaTag(
            $this->mediaRepository->reveal(),
            $this->mediaManager->reveal()
        );
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

        $this->mediaRepository->findMediaDisplayInfo([$media['id']], 'de')->shouldBeCalled();
        $this->mediaRepository->findMediaDisplayInfo([$media['id']], 'de')->willReturn([$media]);
        $this->mediaManager->getUrl($media['id'], $media['name'], $media['version'])->willReturn('/test-url');

        $result = $this->mediaTag->parseAll([$tag => $attributes], 'de');

        $this->assertEquals([$tag => $expected], $result);
    }

    public function testParseAllMultipleTags()
    {
        $media1 = $this->createMedia(1, 'Media-Title-1', 'Media-Name-1', 1);
        $media2 = $this->createMedia(2, 'Media-Title-2', 'Media-Name-2', 1);

        $this->mediaRepository->findMediaDisplayInfo([$media1['id'], $media2['id']], 'de')->shouldBeCalled();
        $this->mediaRepository->findMediaDisplayInfo(
            [$media1['id'], $media2['id']],
            'de'
        )->willReturn([$media1, $media2]);

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
        $this->mediaRepository->findMediaDisplayInfo(['1'], 'de')->shouldBeCalled();
        $this->mediaRepository->findMediaDisplayInfo(['1'], 'de')->willReturn([]);

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

        $this->mediaRepository->findMediaDisplayInfo([$media['id']], 'de')->shouldBeCalled();
        $this->mediaRepository->findMediaDisplayInfo([$media['id']], 'de')->willReturn([$media]);

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
        $this->mediaRepository->findMediaDisplayInfo(['1'], 'de')->shouldBeCalled();
        $this->mediaRepository->findMediaDisplayInfo(['1'], 'de')->willReturn([]);

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

        $this->mediaRepository->findMediaDisplayInfo([$media['id'], '2'], 'de')->shouldBeCalled();
        $this->mediaRepository->findMediaDisplayInfo([$media['id'], '2'], 'de')->willReturn([$media]);

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
