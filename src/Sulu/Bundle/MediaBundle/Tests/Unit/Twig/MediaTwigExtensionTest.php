<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Twig\MediaTwigExtension;

class MediaTwigExtensionTest extends TestCase
{
    public function testResolveMedia()
    {
        $entity = $this->prophesize(Media::class);
        $entity->getId()->willReturn(1);
        $entity->setLocale('de')->willReturn($entity->reveal());

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $mediaManager->addFormatsAndUrl(Argument::type(Media::class))->will(
            function($args) {
                return $args[0];
            }
        );

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $result = $extension->resolveMediaFunction($entity->reveal(), 'de');

        $this->assertInstanceOf(Media::class, $result);
    }

    public function testResolveMediaById()
    {
        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getById(1, 'de')->shouldBeCalled();

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $extension->resolveMediaFunction(1, 'de');
    }

    public function testResolveMedias()
    {
        $media1 = $this->prophesize(Media::class);
        $media2 = $this->prophesize(Media::class);
        $media1->getId()->willReturn(1);
        $media2->getId()->willReturn(2);
        $media1->setLocale('de')->willReturn($media1->reveal());
        $media2->setLocale('de')->willReturn($media2->reveal());

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getByIds(Argument::any(), Argument::any())->shouldNotBeCalled();
        $mediaManager->addFormatsAndUrl(Argument::type(Media::class))->will(
            function($args) {
                return $args[0];
            }
        );

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $result = $extension->resolveMediasFunction([$media1->reveal(), $media2->reveal()], 'de');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Media::class, $result[0]);
        $this->assertInstanceOf(Media::class, $result[1]);
    }

    public function testResolveMediasById()
    {
        $media1 = $this->prophesize(Media::class);
        $media2 = $this->prophesize(Media::class);
        $media1->getId()->willReturn(1);
        $media2->getId()->willReturn(2);
        $media1->setLocale('de')->willReturn();
        $media2->setLocale('de')->willReturn();

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getByIds([1, 2], 'de')->willReturn([
            $media1->reveal(),
            $media2->reveal(),
        ]);
        $mediaManager->addFormatsAndUrl(Argument::type(Media::class))->shouldNotBeCalled();

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $extension->resolveMediasFunction([1, 2], 'de');
    }

    public function testResolveNullMedia()
    {
        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $extension = new MediaTwigExtension($mediaManager->reveal());

        $this->assertNull($extension->resolveMediaFunction(null, 'en'));
    }

    public function testResolveNotExistMedia()
    {
        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $extension = new MediaTwigExtension($mediaManager->reveal());
        $mediaManager->getById(404, 'en')->will(function($args) {
            throw new MediaNotFoundException($args[0]);
        });

        $this->assertNull($extension->resolveMediaFunction(404, 'en'));
    }
}
