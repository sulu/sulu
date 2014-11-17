<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Preview;

use Liip\ThemeBundle\ActiveTheme;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\PhpcrCacheProvider;
use Sulu\Bundle\ContentBundle\Preview\Preview;
use Sulu\Bundle\ContentBundle\Preview\PreviewCacheProviderInterface;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Bundle\ContentBundle\Preview\PreviewRenderer;
use Sulu\Bundle\ContentBundle\Preview\RdfaCrawler;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * @group functional
 * @group preview
 */
class PreviewTest extends PhpcrTestCase
{
    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var PreviewCacheProviderInterface
     */
    private $previewCache;

    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    /**
     * @var PreviewRenderer
     */
    private $renderer;

    /**
     * @var RdfaCrawler
     */
    private $crawler;

    /**
     * @var ControllerResolverInterface
     */
    private $resolver;

    protected function setUp()
    {
        $this->prepareControllerResolver();

        $this->prepareWebspaceManager();
        $this->prepareMapper();

        $this->activeTheme = new ActiveTheme('test', array('test'));
        $this->previewCache = new PhpcrCacheProvider($this->mapper, $this->sessionManager);
        $this->renderer = new PreviewRenderer($this->activeTheme, $this->resolver, $this->webspaceManager);
        $this->crawler = new RdfaCrawler();

        $this->preview = new Preview($this->contentTypeManager, $this->previewCache, $this->renderer, $this->crawler);
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

            $webspace->setNavigation(new Navigation(array(new NavigationContext('main', array()))));

            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager->expects($this->any())
                ->method('findWebspaceByKey')
                ->will($this->returnValue($webspace));
        }
    }

    public function prepareControllerResolver()
    {
        $controller = $this->getMock('\Sulu\Bundle\WebsiteBundle\Controller\WebsiteController', array('indexAction'));
        $controller->expects($this->any())
            ->method('indexAction')
            ->will($this->returnCallback(array($this, 'indexCallback')));

        $this->resolver = $this->getMock('\Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
        $this->resolver->expects($this->any())
            ->method('getController')
            ->will($this->returnValue(array($controller, 'indexAction')));
    }

    public function structureCallback()
    {
        return $this->prepareStructureMock();
    }

    public function prepareStructureMock()
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure\Page',
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
                new Property(
                    'title',
                    'title',
                    'text_line',
                    false,
                    true,
                    1,
                    1,
                    array()
                )
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    'url', 'url', 'resource_locator',
                    false,
                    true,
                    1,
                    1,
                    array(),
                    array(new PropertyTag('sulu.rlp', 1))
                )
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

    /**
     * @return StructureInterface[]
     */
    private function prepareData()
    {
        $data = array(
            array(
                'title' => 'Test1',
                'url' => '/test-1',
                'article' => 'Lorem Ipsum dolorem apsum',
                'block' => array(
                    array(
                        'type' => 'type1',
                        'title' => 'Block-Title-1',
                        'article' => array('Block-Article-1-1', 'Block-Article-1-2')
                    ),
                    array(
                        'type' => 'type1',
                        'title' => 'Block-Title-2',
                        'article' => array('Block-Article-2-1', 'Block-Article-2-2')
                    )
                )
            ),
            array(
                'title' => 'Test2',
                'url' => '/test-2',
                'article' => 'asdfasdf',
                'block' => array(
                    array(
                        'type' => 'type1',
                        'title' => 'Block-Title-2',
                        'article' => array('Block-Article-2-1', 'Block-Article-2-2')
                    )
                )
            )
        );

        $data[0] = $this->mapper->save($data[0], 'overview', 'default', 'en', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'default', 'en', 1);

        return $data;
    }

    public function renderCallback()
    {
        $args = func_get_args();
        /** @var StructureInterface $content */
        $content = $args[1]['content'];

        $result = $this->render($content->getPropertyValue('title'), $content->getPropertyValue('article'), $content->getPropertyValue('block'));

        return $result;
    }

    public function indexCallback(StructureInterface $structure, $preview = false, $partial = false)
    {
        return new Response(
            $this->render(
                $structure->getPropertyValue('title'),
                $structure->getPropertyValue('article'),
                $structure->getPropertyValue('block'),
                $partial
            )
        );
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

    public function testStartPreview()
    {
        $data = $this->prepareData();

        $content = $this->preview->start(1, $data[0]->getUuid(), 'default', 'en');
        // check result
        $this->assertEquals('Test1', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));

        // check cache
        $node = $this->sessionManager->getTempNode('default', 1)->getNode('preview');
        $this->assertNotNull($node);
        $this->assertEquals('Test1', $node->getPropertyValue('i18n:en-title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $node->getPropertyValue('i18n:en-article'));
    }

    public function testStopPreview()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'default', 'en');
        $this->assertTrue($this->previewCache->contains(1, $data[0]->getUuid(), 'default', 'en'));

        $this->preview->stop(1, $data[0]->getUuid(), 'default', 'en');
        $this->assertFalse($this->previewCache->contains(1, $data[0]->getUuid(), 'default', 'en'));
    }

    public function testUpdate()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'default', 'en');
        $this->preview->updateProperty(1, $data[0]->getUuid(), 'default', 'en', 'title', 'aaaa');
        $content = $this->preview->getChanges(1, $data[0]->getUuid(), 'default', 'en');

        // check result
        $this->assertEquals(['aaaa', 'PREF: aaaa'], $content['title']);

        // check cache
        $this->assertTrue($this->previewCache->contains(1, $data[0]->getUuid(), 'default', 'en'));
        $content = $this->previewCache->fetchStructure(1, 'default', 'en');
        $this->assertEquals('aaaa', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));
    }

    public function testUpdateSequence()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'default', 'en');
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'default',
            'en',
            'block,0,article,0',
            'New-Block-Article-1-1'
        );
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'default',
            'en',
            'block,0,article,1',
            'New-Block-Article-1-2'
        );
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'default',
            'en',
            'block,0,title',
            'New-Block-Title-1'
        );
        $this->preview->updateProperty(
            1,
            $data[0]->getUuid(),
            'default',
            'en',
            'block,1,title',
            'New-Block-Title-2'
        );
        $changes = $this->preview->getChanges(
            1,
            $data[0]->getUuid(),
            'default',
            'en'
        );

        // check result
        $this->assertEquals(['New-Block-Article-1-1', 'New-Block-Article-1-2'], $changes['block,0,article']);

        $this->assertEquals(1, sizeof($changes['block,0']));
        $this->assertEquals(
            "<h1 property=\"title\">New-Block-Title-1</h1>\n" .
            "<ul>\n" .
            "<li property=\"article\">New-Block-Article-1-1</li>\n" .
            "<li property=\"article\">New-Block-Article-1-2</li>\n" .
            "</ul>",
            $changes['block,0'][0]
        );
        $this->assertEquals(1, sizeof($changes['block,1']));
        $this->assertEquals(
            "<h1 property=\"title\">New-Block-Title-2</h1>\n" .
            "<ul>\n" .
            "<li property=\"article\">Block-Article-2-1</li>\n" .
            "<li property=\"article\">Block-Article-2-2</li>\n" .
            "</ul>",
            $changes['block,1'][0]
        );

        // check cache
        $this->assertTrue($this->previewCache->contains(1, $data[0]->getUuid(), 'default', 'en'));
        $content = $this->previewCache->fetchStructure(1, 'default', 'en');
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
            $content->getPropertyValue('block')
        );
    }

    public function testRender()
    {
        $data = $this->prepareData();

        $this->preview->start(1, $data[0]->getUuid(), 'default', 'en');
        $response = $this->preview->render(
            1,
            $data[0]->getUuid(),
            'default',
            'en'
        );

        $expected = $this->render(
            'Test1',
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
        $data = $this->prepareData();

        // start preview from FORM
        $content = $this->preview->start(1, $data[0]->getUuid(), 'default', 'en');
        $this->assertEquals('Test1', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));

        // render PREVIEW
        $response = $this->preview->render(1, $data[0]->getUuid(), 'default', 'en');
        $expected = $this->render(
            'Test1',
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
        $content = $this->preview->updateProperty(1, $data[0]->getUuid(), 'default', 'en', 'title', 'New Title');
        $this->assertEquals('New Title', $content->getPropertyValue('title'));
        $this->assertEquals('Lorem Ipsum dolorem apsum', $content->getPropertyValue('article'));

        $content = $this->preview->updateProperty(1, $data[0]->getUuid(), 'default', 'en', 'article', 'asdf');
        $this->assertEquals('New Title', $content->getPropertyValue('title'));
        $this->assertEquals('asdf', $content->getPropertyValue('article'));

        // update PREVIEW
        $changes = $this->preview->getChanges(1, $data[0]->getUuid(), 'default','en');
        $this->assertEquals(2, sizeof($changes));
        $this->assertEquals(['New Title', 'PREF: New Title'], $changes['title']);
        $this->assertEquals(['asdf'], $changes['article']);

        // update PREVIEW
        $changes = $this->preview->getChanges(1, $data[0]->getUuid(), 'default', 'en');
        $this->assertEquals(0, sizeof($changes));

        // rerender PREVIEW
        $response = $this->preview->render(1, $data[0]->getUuid(), 'default', 'en');
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
