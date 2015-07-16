<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Twig;

use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Api\Media as MediaApi;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Twig\MediaTwigExtension;

class MediaTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveMedia()
    {
        $entity = $this->prophesize(Media::class);
        $entity->getId()->willReturn(1);

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $mediaManager->addFormatsAndUrl(Argument::type(MediaApi::class))->will(
            function ($args) {
                return $args[0];
            }
        );

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $result = $extension->resolveMediaFunction($entity->reveal(), 'de');

        $this->assertInstanceOf(MediaApi::class, $result);
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
        $entities = array($this->prophesize(Media::class), $this->prophesize(Media::class));
        $entities[0]->getId()->willReturn(1);
        $entities[1]->getId()->willReturn(2);

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getByIds(Argument::any(), Argument::any())->shouldNotBeCalled();
        $mediaManager->addFormatsAndUrl(Argument::type(MediaApi::class))->will(
            function ($args) {
                return $args[0];
            }
        );

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $result = $extension->resolveMediasFunction(array($entities[0]->reveal(), $entities[1]->reveal()), 'de');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(MediaApi::class, $result[0]);
        $this->assertInstanceOf(MediaApi::class, $result[1]);
    }

    public function testResolveMediasById()
    {
        $entities = array($this->prophesize(Media::class), $this->prophesize(Media::class));
        $entities[0]->getId()->willReturn(1);
        $entities[1]->getId()->willReturn(2);

        $apiEntities = array(new MediaApi($entities[0]->reveal(), 'de'), new MediaApi($entities[1]->reveal(), 'de'));

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getByIds(array(1, 2), 'de')->willReturn($apiEntities);
        $mediaManager->addFormatsAndUrl(Argument::type(MediaApi::class))->shouldNotBeCalled();

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $extension->resolveMediasFunction(array(1, 2), 'de');
    }

    public function testResolveMediasMixed()
    {
        $entities = array(
            $this->prophesize(Media::class),
            $this->prophesize(Media::class),
            $this->prophesize(Media::class)
        );
        $entities[0]->getId()->willReturn(1);
        $entities[1]->getId()->willReturn(2);
        $entities[2]->getId()->willReturn(3);

        $apiEntities = array(new MediaApi($entities[2]->reveal(), 'de'));

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getByIds(array(3), 'de')->willReturn($apiEntities);
        $mediaManager->addFormatsAndUrl(Argument::type(MediaApi::class))->will(
            function ($args) {
                return $args[0];
            }
        );

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $result = $extension->resolveMediasFunction(array($entities[0]->reveal(), 3, $entities[1]->reveal()), 'de');

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals(3, $result[1]->getId());
        $this->assertEquals(2, $result[2]->getId());
    }
}
