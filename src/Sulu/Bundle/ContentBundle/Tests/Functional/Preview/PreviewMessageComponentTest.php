<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Preview;

use Liip\ThemeBundle\ActiveTheme;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\PhpcrCacheProvider;
use Sulu\Bundle\ContentBundle\Preview\Preview;
use Sulu\Bundle\ContentBundle\Preview\PreviewCacheProviderInterface;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Bundle\ContentBundle\Preview\PreviewMessageComponent;
use Sulu\Bundle\ContentBundle\Preview\PreviewRenderer;
use Sulu\Bundle\ContentBundle\Preview\RdfaCrawler;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class PreviewMessageComponentTest extends PhpcrTestCase
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

    /**
     * @var PreviewMessageComponent
     */
    private $component;

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
        $this->component = new PreviewMessageComponent($this->preview, $this->getMock('\Psr\Log\LoggerInterface'));
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
                new Property(
                    'title',
                    'title',
                    'text_line',
                    false,
                    true,
                    1,
                    1,
                    array(),
                    array(new PropertyTag('sulu.node.name', 1))
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

        $result = $this->render(
            $content->getPropertyValue('title'),
            $content->getPropertyValue('article'),
            $content->getPropertyValue('block')
        );

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

    private function prepareClient(
        callable $sendCallback,
        $sendExpects = null,
        $name = 'CON_1',
        callable $closeCallback = null,
        $closeExpects = null
    ) {
        if ($sendExpects == null) {
            $sendExpects = $this->any();
        }
        if ($closeExpects == null) {
            $closeExpects = $this->any();
        }
        $client = $this->getMock('Ratchet\ConnectionInterface');

        $client
            ->expects($sendExpects)
            ->method('send')
            ->will($this->returnCallback($sendCallback));

        if ($closeCallback != null) {
            $client
                ->expects($closeExpects)
                ->method('close')
                ->will($this->returnCallback($closeCallback));
        }

        $client->resourceId = $name;

        return $client;
    }

    public function testStart()
    {
        $data = $this->prepareData();

        $i = -1;

        $clientForm = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);
                $this->assertEquals($data->msg, 'OK');

                $i++;
                if ($i == 0) {
                    $this->assertEquals($data->other, false);
                } else {
                    $this->assertEquals($data->other, true);
                }
            },
            $this->exactly(2),
            'form'
        );
        $clientPreview = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);
                $this->assertEquals($data->msg, 'OK');
                $this->assertEquals($data->other, true);
            },
            $this->once(),
            'preview'
        );

        $this->component->onMessage(
            $clientForm,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientPreview,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'preview',
                    'user' => '1'
                )
            )
        );
    }

    public function testUpdate()
    {
        $data = $this->prepareData();

        $i = 0;
        $clientForm1 = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);

                if ($i == 0 && $data->command == 'start') {
                    $this->assertEquals($data->msg, 'OK');
                    $this->assertEquals($data->other, false);
                    $i++;
                } elseif ($i == 1 && $data->command == 'start') {
                    $this->assertEquals($data->msg, 'OK');
                    $this->assertEquals($data->other, true);
                } elseif (($i == 2 || $i == 3) && $data->command == 'update') {
                    $this->assertEquals($data->msg, 'OK');
                } else {
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'form1'
        );
        $clientPreview1 = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);

                if ($i == 1 && $data->command == 'start') {
                    $this->assertEquals($data->msg, 'OK');
                    $this->assertEquals($data->other, true);
                    $i++;
                } elseif ($i == 2 && $data->command == 'changes') {
                    $this->assertEquals('asdf', $data->changes->title[0]);
                    $this->assertEquals('PREF: asdf', $data->changes->title[1]);
                    $i++;
                } elseif ($i == 3 && $data->command == 'changes') {
                    $this->assertEquals('qwertz', $data->changes->article[0]);
                } else {
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'preview1'
        );

        $clientForm2 = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);

                if ($data->command != 'start') {
                    // no update will be sent
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'form2'
        );
        $this->component->onMessage(
            $clientForm2,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1'
                )
            )
        );

        $clientPreview2 = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);

                if ($data->command != 'start') {
                    // no update will be sent
                    $this->assertTrue(false);
                }
            },
            $this->any(),
            'preview2'
        );
        $this->component->onMessage(
            $clientPreview2,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[0]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'preview',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientPreview1,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'preview',
                    'user' => '1'
                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'update',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1',
                    'changes' => array(
                        'title' => 'asdf'
                    )

                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'update',
                    'content' => $data[1]->getUuid(),
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1',
                    'changes' => array(
                        'article' => 'qwertz'
                    )
                )
            )
        );
    }
}
