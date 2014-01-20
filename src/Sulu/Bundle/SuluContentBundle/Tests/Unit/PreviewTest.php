<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\Preview;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class PreviewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var Cache
     */
    private $cache;

    protected function setUp()
    {
        $mapper = $this->prepareMapperMock();
        $templating = $this->prepareTemplatingMock();
        $structureManager=$this->prepareStructureManagerMock();
        $controllerResolver = $this->prepareControllerResolver();
        $this->cache = new ArrayCache();

        $this->preview = new Preview($templating, $this->cache, $mapper, $structureManager, $controllerResolver, 3600);
    }

    public function prepareControllerResolver()
    {
        $controller = $this->getMock('\Sulu\Bundle\WebsiteBundle\Controller\WebsiteController', array('indexAction'));
        $controller->expects($this->any())
            ->method('indexAction')
            ->will($this->returnCallback(array($this, 'indexCallback')));

        $resolver = $this->getMock('\Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
        $resolver->expects($this->any())
            ->method('getController')
            ->will($this->returnValue(array($controller, 'indexAction')));

        return $resolver;
    }

    public function prepareStructureManagerMock()
    {
        $structureManagerMock = $this->getMock('\Sulu\Component\Content\StructureManagerInterface');
        $structureManagerMock->expects($this->any())
            ->method('getStructure')
            ->will($this->returnValue(true));

        return $structureManagerMock;
    }

    public function prepareTemplatingMock()
    {
        $templating = $this->getMock('\Symfony\Component\Templating\EngineInterface');
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnCallback(array($this, 'renderCallback')));

        return $templating;
    }

    public function prepareMapperMock()
    {
        $structure = $this->prepareStructureMock();
        $mapper = $this->getMock('\Sulu\Component\Content\Mapper\ContentMapperInterface');
        $mapper->expects($this->any())
            ->method('load')
            ->will($this->returnValue($structure));

        return $mapper;
    }

    public function prepareStructureMock()
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array('overview', 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'add'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property('title', 'text_line')
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('url', 'resource_locator')
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('article', 'text_area')
            )
        );

        $structureMock->getProperty('title')->setValue('Title');
        $structureMock->getProperty('article')->setValue('Lorem Ipsum dolorem apsum');

        return $structureMock;
    }

    public function renderCallback()
    {
        $args = func_get_args();
        $template = $args[0];
        /** @var StructureInterface $content */
        $content = $args[1]['content'];

        $result = $this->render($content->title, $content->article);
        return $result;
    }

    public function indexCallback(StructureInterface $structure, $preview = false, $partial = false)
    {
        return new Response($this->render($structure->title, $structure->article, $partial));

    }

    public function render($title, $article, $partial = false)
    {
        $template = '<h1 property="title">%s</h1><div property="article">%s</div>';
        if (!$partial) {
            $template = '<html vocab="http://schema.org/" typeof="Content"><body>' . $template . '</body></html>';
        }

        return sprintf($template, $title, $article);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testStartPreview()
    {
        $content = $this->preview->start(1, '123-123-123', 'default', 'en');
        // check result
        $this->assertEquals('Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);

        // check cache
        $this->assertTrue($this->cache->contains('1:123-123-123'));
        $content = $this->cache->fetch('1:123-123-123');
        $this->assertEquals('Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);
    }

    public function testStopPreview()
    {
        $this->preview->start(1, '123-123-123', 'default', 'en');
        $this->assertTrue($this->cache->contains('1:123-123-123'));

        $this->preview->stop(1, '123-123-123');
        $this->assertFalse($this->cache->contains('1:123-123-123'));
    }

    public function testUpdate()
    {
        $this->preview->start(1, '123-123-123', 'default', 'en');
        $this->preview->update(1, '123-123-123', 'title', 'aaaa');
        $content = $this->preview->getChanges(1, '123-123-123');

        // check result
        $this->assertEquals('aaaa', $content['title']['content']);

        // check cache
        $this->assertTrue($this->cache->contains('1:123-123-123'));
        $content = $this->cache->fetch('1:123-123-123');
        $this->assertEquals('aaaa', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);
    }

    public function testRender()
    {
        $this->preview->start(1, '123-123-123', 'default', 'en');
        $response = $this->preview->render(1, '123-123-123');

        $expected = $this->render('Title', 'Lorem Ipsum dolorem apsum');
        $this->assertEquals($expected, $response);
    }

    public function testRealScenario()
    {
        // start preview from FORM
        $content = $this->preview->start(1, '123-123-123', 'default', 'en');
        $this->assertEquals('Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);

        // render PREVIEW
        $response = $this->preview->render(1, '123-123-123');
        $expected = $this->render('Title', 'Lorem Ipsum dolorem apsum');
        $this->assertEquals($expected, $response);

        // change a property in FORM
        $content = $this->preview->update(1, '123-123-123', 'title', 'New Title');
        $this->assertEquals('New Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);

        $content = $this->preview->update(1, '123-123-123', 'article', 'asdf');
        $this->assertEquals('New Title', $content->title);
        $this->assertEquals('asdf', $content->article);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, '123-123-123');
        $this->assertEquals(2, sizeof($changes));
        $this->assertEquals('New Title', $changes['title']['content']);
        $this->assertEquals('title', $changes['title']['property']);
        $this->assertEquals('asdf', $changes['article']['content']);
        $this->assertEquals('article', $changes['article']['property']);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, '123-123-123');
        $this->assertEquals(0, sizeof($changes));

        // rerender PREVIEW
        $response = $this->preview->render(1, '123-123-123');
        $expected = $this->render('New Title', 'asdf');
        $this->assertEquals($expected, $response);
    }
}
