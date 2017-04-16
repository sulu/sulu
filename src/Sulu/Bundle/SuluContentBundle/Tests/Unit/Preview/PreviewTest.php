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
use Liip\ThemeBundle\ActiveTheme;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\Preview;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;
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

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    protected function setUp()
    {
        $mapper = $this->prepareMapperMock();
        $templating = $this->prepareTemplatingMock();
        $structureManager=$this->prepareStructureManagerMock();
        $contentTypeManager = $this->prepareContentTypeManager();
        $controllerResolver = $this->prepareControllerResolver();
        $this->cache = new ArrayCache();

        $activeTheme = new ActiveTheme('test', array('test'));

        $this->prepareWebspaceManager();

        $this->preview = new Preview(
            $templating,
            $this->cache,
            $mapper,
            $structureManager,
            $contentTypeManager,
            $controllerResolver,
            $this->webspaceManager,
            $activeTheme,
            3600
        );
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $webspace = new Webspace();
            $en = new Localization();
            $en->setLanguage('en');
            $en_us = new Localization();
            $en_us->setLanguage('en');
            $en_us->setCountry('us');
            $en_us->setParent($en);
            $en->addChild($en_us);

            $de = new Localization();
            $de->setLanguage('de');
            $de_at = new Localization();
            $de_at->setLanguage('de');
            $de_at->setCountry('at');
            $de_at->setParent($de);
            $de->addChild($de_at);

            $theme = new Theme();
            $theme->setKey('test');
            $webspace->setTheme($theme);

            $es = new Localization();
            $es->setLanguage('es');

            $webspace->addLocalization($en);
            $webspace->addLocalization($de);
            $webspace->addLocalization($es);

            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager->expects($this->any())
                ->method('findWebspaceByKey')
                ->will($this->returnValue($webspace));
        }
    }

    public function prepareContentTypeManager()
    {
        $container = $this->getMock('Sulu\Component\Content\ContentTypeManagerInterface');

        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('text_line', new TextLine('')),
                        array('text_area', new TextArea(''))
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
        $structureMock->setLanguageCode('en');
        $structureMock->setWebspaceKey('sulu_io');


        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property('title', 'title', 'text_line')
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('url', 'url', 'resource_locator')
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('article', 'article', 'text_area')
            )
        );

        $block = new BlockProperty('block', 'block', false, false, 4, 2);
        $type1 = new BlockPropertyType('type1', '');

        $prop = new Property('title', 'title', 'text_line');
        $type1->addChild($prop);

        $prop = new Property('article', 'article', 'text_area', false, false, 4, 2);
        $type1->addChild($prop);

        $block->addType($type1);
        $block->setValue(
            array(
                array(
                    'type' => 'type1',
                    'title'=>'Block-Title-1',
                    'article' => array('Block-Article-1-1', 'Block-Article-1-2')
                ),
                array(
                    'type' => 'type1',
                    'title'=>'Block-Title-2',
                    'article' => array('Block-Article-2-1', 'Block-Article-2-2')
                )
            )
        );




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
        $content = $this->preview->start(1, '123-123-123', 'default', 'overview', 'en');
        // check result
        $this->assertEquals('Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);

        // check cache
        $this->assertTrue($this->cache->contains('U1:C123-123-123:Toverview:Len'));
        $content = $this->cache->fetch('U1:C123-123-123:Toverview:Len');
        $this->assertEquals('Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);
    }

    public function testStopPreview()
    {
        $this->preview->start(1, '123-123-123', 'default', 'overview', 'en');
        $this->assertTrue($this->cache->contains('U1:C123-123-123:Toverview:Len'));

        $this->preview->stop(1, '123-123-123', 'overview', 'en');
        $this->assertFalse($this->cache->contains('U1:C123-123-123:Toverview:Len'));
    }

    public function testUpdate()
    {
        $this->preview->start(1, '123-123-123', 'sulu_io', 'overview', 'en', 'default', 'en');
        $this->preview->update(1, '123-123-123', 'sulu_io', 'overview', 'en', 'title', 'aaaa');
        $content = $this->preview->getChanges(1, '123-123-123', 'overview', 'en');

        // check result
        $this->assertEquals(['aaaa', 'PREF: aaaa'], $content['title']['content']);

        // check cache
        $this->assertTrue($this->cache->contains('U1:C123-123-123:Toverview:Len'));
        $content = $this->cache->fetch('U1:C123-123-123:Toverview:Len');
        $this->assertEquals('aaaa', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);
    }

    public function testUpdateSequence()
    {
        $this->preview->start(1, '123-123-123', 'sulu_io', 'overview', 'en');
        $this->preview->update(
            1,
            '123-123-123',
            'sulu_io',
            'overview',
            'en',
            'block,0,article,0',
            'New-Block-Article-1-1'
        );
        $this->preview->update(
            1,
            '123-123-123',
            'sulu_io',
            'overview',
            'en',
            'block,0,article,1',
            'New-Block-Article-1-2'
        );
        $this->preview->update(1, '123-123-123', 'sulu_io', 'overview', 'en', 'block,0,title', 'New-Block-Title-1');
        $this->preview->update(1, '123-123-123', 'sulu_io', 'overview', 'en', 'block,1,title', 'New-Block-Title-2');
        $changes = $this->preview->getChanges(1, '123-123-123', 'overview', 'en');

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
        $this->assertTrue($this->cache->contains('U1:C123-123-123:Toverview:Len'));
        $content = $this->cache->fetch('U1:C123-123-123:Toverview:Len');
        $this->assertEquals(
            array(
                array(
                    'type' => 'type1',
                    'title' => 'New-Block-Title-1',
                    'article' => array(
                        'New-Block-Article-1-1',
                        'New-Block-Article-1-2'
                    )
                ),
                array(
                    'type' => 'type1',
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
        $this->preview->start(1, '123-123-123', 'sulu_io', 'overview', 'en');
        $response = $this->preview->render(
            1,
            '123-123-123',
            'overview',
            'en'
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
        $content = $this->preview->start(1, '123-123-123', 'sulu_io', 'overview', 'en');
        $this->assertEquals('Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);

        // render PREVIEW
        $response = $this->preview->render(1, '123-123-123', 'overview', 'en');
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
        $content = $this->preview->update(1, '123-123-123', 'sulu_io', 'overview', 'en', 'title', 'New Title');
        $this->assertEquals('New Title', $content->title);
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->article);

        $content = $this->preview->update(1, '123-123-123', 'sulu_io', 'overview', 'en', 'article', 'asdf');
        $this->assertEquals('New Title', $content->title);
        $this->assertEquals('asdf', $content->article);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, '123-123-123', 'overview', 'en');
        $this->assertEquals(2, sizeof($changes));
        $this->assertEquals(['New Title', 'PREF: New Title'], $changes['title']['content']);
        $this->assertEquals('title', $changes['title']['property']);
        $this->assertEquals(['asdf'], $changes['article']['content']);
        $this->assertEquals('article', $changes['article']['property']);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, '123-123-123', 'overview', 'en');
        $this->assertEquals(0, sizeof($changes));

        // rerender PREVIEW
        $response = $this->preview->render(1, '123-123-123', 'overview', 'en');
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
