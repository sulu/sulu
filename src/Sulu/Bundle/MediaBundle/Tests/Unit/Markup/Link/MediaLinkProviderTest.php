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
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Markup\Link\MediaLinkProvider;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    public function setUp(): void
    {
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans('sulu_media.media', [], 'admin')
            ->willReturn('Media');

        $this->mediaLinkProvider = new MediaLinkProvider(
            $this->mediaRepository->reveal(),
            $this->mediaManager->reveal(),
            $this->translator->reveal()
        );
    }

    public function testGetConfiguration(): void
    {
        /** @var LinkConfigurationBuilder $configurationBuilder */
        $configurationBuilder = $this->mediaLinkProvider->getConfigurationBuilder();

        $this->assertEquals(
            new LinkConfiguration(
                'Media',
                'media',
                '',
                ['title'],
                '',
                '',
                '',
                ['_blank', '_self', '_parent', '_top'],
            ),
            $configurationBuilder->getLinkConfiguration()
        );
    }

    public function testPreload(): void
    {
        $this->mediaRepository->findMediaDisplayInfo([3, 6], 'de')->willReturn([
            ['id' => 3, 'title' => 'Test1', 'defaultTitle' => 'defaultTitle1', 'name' => 'test1.jpg', 'version' => 3],
            ['id' => 6, 'title' => null, 'defaultTitle' => 'defaultTitle2', 'name' => 'test2.jpg', 'version' => 1],
        ]);

        $this->mediaManager->getUrl(3, 'test1.jpg', 3)->willReturn('/test1.jpg?version=3');
        $this->mediaManager->getUrl(6, 'test2.jpg', 1)->willReturn('/test2.jpg?version=1');

        $mediaLinks = [...$this->mediaLinkProvider->preload([3, 6], 'de', false)];

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
