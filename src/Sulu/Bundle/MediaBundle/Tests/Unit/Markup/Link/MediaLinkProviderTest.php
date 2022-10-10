<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Markup\Link;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Markup\Link\MediaLinkProvider;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

class MediaLinkProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MediaRepositoryInterface>
     */
    private $mediaRepository;

    /**
     * @var ObjectProphecy<MediaManagerInterface>
     */
    private $mediaManager;

    /**
     * @var MediaLinkProvider
     */
    private $mediaLinkProvider;

    public function setUp(): void
    {
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);

        $this->mediaLinkProvider = new MediaLinkProvider(
            $this->mediaRepository->reveal(),
            $this->mediaManager->reveal()
        );
    }

    public function testGetConfiguration(): void
    {
        $this->assertNull($this->mediaLinkProvider->getConfiguration());
    }

    public function testPreload(): void
    {
        $this->mediaRepository->findMediaDisplayInfo([3, 6], 'de')->willReturn([
            ['id' => 3, 'title' => 'Test1', 'defaultTitle' => 'defaultTitle1', 'name' => 'test1.jpg', 'version' => 3],
            ['id' => 6, 'title' => null, 'defaultTitle' => 'defaultTitle2', 'name' => 'test2.jpg', 'version' => 1],
        ]);

        $this->mediaManager->getUrl(3, 'test1.jpg', 3)->willReturn('/test1.jpg?version=3');
        $this->mediaManager->getUrl(6, 'test2.jpg', 1)->willReturn('/test2.jpg?version=1');

        $mediaLinks = $this->mediaLinkProvider->preload([3, 6], 'de', false);

        $this->assertEquals(3, $mediaLinks[0]->getId());
        $this->assertEquals('Test1', $mediaLinks[0]->getTitle());
        $this->assertEquals('/test1.jpg?version=3', $mediaLinks[0]->getUrl());
        $this->assertTrue($mediaLinks[0]->isPublished());

        $this->assertEquals(6, $mediaLinks[1]->getId());
        $this->assertEquals('defaultTitle2', $mediaLinks[1]->getTitle());
        $this->assertEquals('/test2.jpg?version=1', $mediaLinks[1]->getUrl());
        $this->assertTrue($mediaLinks[1]->isPublished());
    }
}
