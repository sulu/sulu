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

use Doctrine\Common\Cache\ArrayCache;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Preview\Preview;
use Sulu\Bundle\ContentBundle\Preview\PreviewMessageComponent;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PreviewMessageComponentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PreviewMessageComponent
     */
    private $component;

    public function testStart()
    {
        $i = -1;

        $clientForm = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);
                $this->assertEquals($data->params->msg, 'OK');

                $i++;
                if ($i == 0) {
                    $this->assertEquals($data->params->other, false);
                } else {
                    $this->assertEquals($data->params->other, true);
                }
            },
            $this->exactly(2),
            'form'
        );
        $clientPreview = $this->prepareClient(
            function ($string) {
                $data = json_decode($string);
                $this->assertEquals($data->params->msg, 'OK');
                $this->assertEquals($data->params->other, true);
            },
            $this->once(),
            'preview'
        );

        $this->component->onMessage(
            $clientForm,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => '123-123-123',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1',
                    'params' => array()
                )
            )
        );

        $this->component->onMessage(
            $clientPreview,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => '123-123-123',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'preview',
                    'user' => '1',
                    'params' => array()
                )
            )
        );
    }

    public function testUpdate()
    {
        $i = 0;
        $clientForm1 = $this->prepareClient(
            function ($string) use (&$i) {
                $data = json_decode($string);

                if ($i == 0 && $data->command == 'start') {
                    $this->assertEquals($data->params->msg, 'OK');
                    $this->assertEquals($data->params->other, false);
                    $i++;
                } elseif ($i == 1 && $data->command == 'start') {
                    $this->assertEquals($data->params->msg, 'OK');
                    $this->assertEquals($data->params->other, true);
                } elseif (($i == 2 || $i == 3) && $data->command == 'update') {
                    $this->assertEquals($data->params->msg, 'OK');
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
                    $this->assertEquals($data->params->msg, 'OK');
                    $this->assertEquals($data->params->other, true);
                    $i++;
                } elseif ($i == 2 && $data->command == 'changes') {
                    $this->assertEquals('asdf', $data->params->changes->title->content[0]);
                    $this->assertEquals('PREF: asdf', $data->params->changes->title->content[1]);
                    $i++;
                } elseif ($i == 3 && $data->command == 'changes') {
                    $this->assertEquals('qwertz', $data->params->changes->article->content[0]);
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
                    'content' => '456-456-456',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1',
                    'params' => array()
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
                    'content' => '456-456-456',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'preview',
                    'user' => '1',
                    'params' => array()
                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => '123-123-123',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1',
                    'params' => array()
                )
            )
        );

        $this->component->onMessage(
            $clientPreview1,
            json_encode(
                array(
                    'command' => 'start',
                    'content' => '123-123-123',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'preview',
                    'user' => '1',
                    'params' => array()
                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'update',
                    'content' => '123-123-123',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1',
                    'params' => array(
                        'changes' => array(
                            'title' => 'asdf'
                        )
                    )
                )
            )
        );

        $this->component->onMessage(
            $clientForm1,
            json_encode(
                array(
                    'command' => 'update',
                    'content' => '123-123-123',
                    'templateKey' => 'overview',
                    'languageCode' => 'de',
                    'webspaceKey' => 'default',
                    'type' => 'form',
                    'user' => '1',
                    'params' => array(
                        'changes' => array(
                            'article' => 'qwertz'
                        )
                    )
                )
            )
        );
    }

    private function prepareClient(
        callable $sendCallback,
        $sendExpects = null,
        $name = 'CON_1',
        callable $closeCallback = null,
        $closeExpects = null
    )
    {
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

    protected function setUp()
    {
        $this->component = $this->prepareComponent();
    }

    private function prepareComponent()
    {
        $preview = $this->preparePreview();
        $component = new PreviewMessageComponent(
            $preview,
            $this->getMock('\Psr\Log\LoggerInterface')
        );

        return $component;
    }

    /**
     * @return SecurityContextInterface
     */
    private function prepareSecurityContext()
    {
        $context = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $user = $this->getMock('Sulu\Component\Security\UserInterface');

        $user
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $token
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $context
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        return $context;
    }

    private function preparePreview()
    {
        $contentTypeManager = $this->prepareContentTypeManager();
        $mapper = $this->prepareMapperMock();
        $templating = $this->prepareTemplatingMock();
        $this->structureManager = $this->prepareStructureManagerMock();
        $controllerResolver = $this->prepareControllerResolver();
        $cache = new ArrayCache();

        return new Preview($templating, $cache, $mapper, $this->structureManager, $contentTypeManager, $controllerResolver, 3600);
    }

    public function prepareContentTypeManager()
    {
        $container = $this->getMock('Sulu\Component\Content\ContentTypeManagerInterface');

        $container->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                array(
                    array('text_line', new TextLine('')),
                    array(
                        'resource_locator',
                        new ResourceLocator($this->getMock(
                            'Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface'
                        ), '')
                    ),
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
            ->will($this->returnValue($this->prepareStructureMock(2)));

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
        $structure = $this->prepareStructureMock(1);
        $mapper = $this->getMock('\Sulu\Component\Content\Mapper\ContentMapperInterface');
        $mapper->expects($this->any())
            ->method('load')
            ->will($this->returnValue($structure));

        return $mapper;
    }

    public function prepareStructureMock($i)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array($i === 1 ? 'overview' : 'simple', 'asdf', 'asdf', 2400)
        );

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

        $structureMock->getProperty('title')->setValue('Title');
        if ($i === 1) {
            $structureMock->getProperty('article')->setValue('Lorem Ipsum dolorem apsum');
        }

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
        $template = '<h1 property="title">%s</h1><h1 property="title">PREF: %s</h1><div property="article">%s</div>';
        if (!$partial) {
            $template = '<html vocab="http://schema.org/" typeof="Content"><body>' . $template . '</body></html>';
        }

        return sprintf($template, $title, $title, $article);
    }
}
