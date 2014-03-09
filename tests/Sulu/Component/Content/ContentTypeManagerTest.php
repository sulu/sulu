<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

class ContentTypeManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContentType()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->once())->method('get')->with('foo.bar');
        $manager = new ContentTypeManager($container, 'foo.');
        $manager->get('bar');
    }
}
