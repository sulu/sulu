<?php
/**
 * Created by IntelliJ IDEA.
 * User: phranck
 * Date: 03.03.14
 * Time: 09:14
 */

namespace Sulu\Component\Content;


class ContentTypeManagerTest extends \PHPUnit_Framework_TestCase {

    public function testGetContentType()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->once())->method('get')->with('foo.bar');
        $manager = new ContentTypeManager($container, 'foo.');
        $manager->get('bar');
    }
}
 