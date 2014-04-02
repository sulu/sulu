<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Preview;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\Preview;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
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
        $container = $this->prepareContainerMock();
        $mapper = $this->prepareMapperMock();
        $templating = $this->prepareTemplatingMock();
        $structureManager=$this->prepareStructureManagerMock();
        $controllerResolver = $this->prepareControllerResolver();
        $this->cache = new ArrayCache();

        $this->preview = new Preview($container, $templating, $this->cache, $mapper, $structureManager, $controllerResolver, 3600);
    }

    public function prepareContainerMock()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('sulu.content.type.text_line', 1, new TextLine('')),
                        array('sulu.content.type.text_area', 1, new TextArea(''))
                    )
                )
            );

        return $container;
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
            ->will($this->returnValue($this->prepareStructureMock()));

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

        $block = new BlockProperty('block', false, false, 4, 2);
        $prop = new Property('title', 'text_line');
        $prop->setValue(array('Block-Title-1', 'Block-Title-2'));
        $block->addChild($prop);
        $prop = new Property('article', 'text_area', false, false, 4, 2);
        $prop->setValue(
            array(
                array('Block-Article-1-1', 'Block-Article-1-2'),
                array('Block-Article-2-1', 'Block-Article-2-2')
            )
        );
        $block->addChild($prop);

        $method->invokeArgs(
            $structureMock,
            array(
                $block
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

        $result = $this->render($content->title, $content->article, $content->block);
        return $result;
    }

    public function indexCallback(StructureInterface $structure, $preview = false, $partial = false)
    {
        return new Response($this->render($structure->title, $structure->article, $structure->block, $partial));

    }

    public function render($title, $article, $block, $partial = false)
    {
        $template = '
            <div id="content" vocab="http://sulu.io/" typeof="Content">
                <h1 property="title">%s</h1>
                <h1 property="title">PREF: %s</h1>
                <div property="article">%s</div>
                <div property="block" typeof="collection">';
        $i = 0;
        foreach ($block as $b) {
            $subTemplate = '';
            foreach ($b['article'] as $a) {
                $subTemplate .= sprintf('<li property="article">%s</li>', $a);
            }
            $template .= sprintf(
                '<div rel="block" typeof="block"><h1 property="title">%s</h1><ul>%s</ul></div>',
                $b['title'],
                $subTemplate
            );
            $i++;
        }
        $template .= '</div></div>';
        if (!$partial) {
            $template = '<html vocab="http://schema.org/" typeof="Content"><body>' . $template . '</body></html>';
        }

        return sprintf($template, $title, $title, $article);
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
        $this->preview->start(1, '123-123-123', '', 'en', 'default', 'en');
        $this->preview->update(1, '123-123-123', '', 'en', 'title', 'aaaa');
        $content = $this->preview->getChanges(1, '123-123-123');

        // check result
        $this->assertEquals(['aaaa', 'PREF: aaaa'], $content['title']['content']);

        // check cache
        $this->assertTrue($this->cache->contains('1:123-123-123'));
        $content = $this->cache->fetch('1:123-123-123');
        $this->assertEquals('aaaa', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);
    }

    public function testUpdateSequence()
    {
        $this->preview->start(1, '123-123-123', '', 'en', 'default', 'en');
        $this->preview->update(1, '123-123-123', '', 'en', 'block,0,article,0', 'New-Block-Article-1-1');
        $this->preview->update(1, '123-123-123', '', 'en', 'block,0,article,1', 'New-Block-Article-1-2');
        $this->preview->update(1, '123-123-123', '', 'en', 'block,0,title', 'New-Block-Title-1');
        $this->preview->update(1, '123-123-123', '', 'en', 'block,1,title', 'New-Block-Title-2');
        $changes = $this->preview->getChanges(1, '123-123-123');

        // check result
        $this->assertEquals(['New-Block-Article-1-1', 'New-Block-Article-1-2'], $changes['block,0,article']['content']);

        $this->assertEquals(1, sizeof($changes['block,0']['content']));
        $this->assertEquals(
            "<h1 property=\"title\">New-Block-Title-1</h1>\n" .
            "<ul>\n" .
            "<li property=\"article\">New-Block-Article-1-1</li>\n" .
            "<li property=\"article\">New-Block-Article-1-2</li>\n" .
            "</ul>",
            $changes['block,0']['content'][0]
        );
        $this->assertEquals(1, sizeof($changes['block,1']['content']));
        $this->assertEquals(
            "<h1 property=\"title\">New-Block-Title-2</h1>\n" .
            "<ul>\n" .
            "<li property=\"article\">Block-Article-2-1</li>\n" .
            "<li property=\"article\">Block-Article-2-2</li>\n" .
            "</ul>",
            $changes['block,1']['content'][0]
        );

        // check cache
        $this->assertTrue($this->cache->contains('1:123-123-123'));
        $content = $this->cache->fetch('1:123-123-123');
        $this->assertEquals(
            array(
                array(
                    'title' => 'New-Block-Title-1',
                    'article' => array(
                        'New-Block-Article-1-1',
                        'New-Block-Article-1-2'
                    )
                ),
                array(
                    'title' => 'New-Block-Title-2',
                    'article' => array(
                        'Block-Article-2-1',
                        'Block-Article-2-2'
                    )
                )
            ),
            $content->block
        );
    }

    public function testRender()
    {
        $this->preview->start(1, '123-123-123', 'default', 'en');
        $response = $this->preview->render(
            1,
            '123-123-123'
        );

        $expected = $this->render(
            'Title',
            'Lorem Ipsum dolorem apsum',
            array(
                array(
                    'title' => 'Block-Title-1',
                    'article' => array(
                        'Block-Article-1-1',
                        'Block-Article-1-2'
                    )
                ),
                array(
                    'title' => 'Block-Title-2',
                    'article' => array(
                        'Block-Article-2-1',
                        'Block-Article-2-2'
                    )
                )
            )
        );
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
        $expected = $this->render(
            'Title',
            'Lorem Ipsum dolorem apsum',
            array(
                array(
                    'title' => 'Block-Title-1',
                    'article' => array(
                        'Block-Article-1-1',
                        'Block-Article-1-2'
                    )
                ),
                array(
                    'title' => 'Block-Title-2',
                    'article' => array(
                        'Block-Article-2-1',
                        'Block-Article-2-2'
                    )
                )
            )
        );
        $this->assertEquals($expected, $response);

        // change a property in FORM
        $content = $this->preview->update(1, '123-123-123', '', 'en', 'title', 'New Title');
        $this->assertEquals('New Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);

        $content = $this->preview->update(1, '123-123-123', '', 'en', 'article', 'asdf');
        $this->assertEquals('New Title', $content->title);
        $this->assertEquals('asdf', $content->article);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, '123-123-123');
        $this->assertEquals(2, sizeof($changes));
        $this->assertEquals(['New Title', 'PREF: New Title'], $changes['title']['content']);
        $this->assertEquals('title', $changes['title']['property']);
        $this->assertEquals(['asdf'], $changes['article']['content']);
        $this->assertEquals('article', $changes['article']['property']);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, '123-123-123');
        $this->assertEquals(0, sizeof($changes));

        // rerender PREVIEW
        $response = $this->preview->render(1, '123-123-123');
        $expected = $this->render(
            'New Title',
            'asdf',
            array(
                array(
                    'title' => 'Block-Title-1',
                    'article' => array(
                        'Block-Article-1-1',
                        'Block-Article-1-2'
                    )
                ),
                array(
                    'title' => 'Block-Title-2',
                    'article' => array(
                        'Block-Article-2-1',
                        'Block-Article-2-2'
                    )
                )
            )
        );
        $this->assertEquals($expected, $response);
    }
}
