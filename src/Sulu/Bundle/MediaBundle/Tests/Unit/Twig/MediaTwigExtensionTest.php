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
        $mediaManager->getById(1, 'de')->shouldBeCalled();

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $extension->resolveMediaFunction($entity->reveal(), 'de');
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
        $mediaManager->getByIds(array(1, 2), 'de')->shouldBeCalled();

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $extension->resolveMediasFunction(array($entities[0]->reveal(), $entities[1]->reveal()), 'de');
    }

    public function testResolveMediasById()
    {
        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getByIds(array(1, 2), 'de')->shouldBeCalled();

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $extension->resolveMediasFunction(array(1, 2), 'de');
    }

    public function testResolveMediasMixed()
    {
        $entities = array($this->prophesize(Media::class), $this->prophesize(Media::class));
        $entities[0]->getId()->willReturn(1);
        $entities[1]->getId()->willReturn(2);

        $mediaManager = $this->prophesize(MediaManagerInterface::class);
        $mediaManager->getByIds(array(1, 3, 2), 'de')->shouldBeCalled();

        $extension = new MediaTwigExtension($mediaManager->reveal());
        $extension->resolveMediasFunction(array($entities[0]->reveal(), 3, $entities[1]->reveal()), 'de');
    }
}
