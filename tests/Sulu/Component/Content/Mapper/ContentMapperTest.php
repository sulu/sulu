<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Jackalope\Session;
use PHPCR\Util\NodeHelper;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Mapper\PhpcrContentMapper;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryService;

class ContentMapperTest extends \PHPUnit_Framework_TestCase
{
    public $sessionService;
    public $structureMock;
    public $structureFactoryMock;
    public $container;
    /**
     * @var PhpcrContentMapper
     */
    protected $mapper;

    /**
     * @var Session
     */
    protected $session;

    public function setUp()
    {
        $this->container = $this->getContainerMock();

        $this->mapper = new ContentMapper('/cmf/contents');
        $this->mapper->setContainer($this->container);

        $this->prepareSession();

        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();

        $cmf = $this->session->getRootNode()->addNode('cmf');
        $cmf->addNode('routes');
        $cmf->addNode('contents');

        $this->session->save();
    }

    private function getContainerMock()
    {
        $this->sessionService = new SessionFactoryService('\Jackalope\RepositoryFactoryJackrabbit', 'http://localhost:8080/server', 'admin', 'admin');

        $this->structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array('overview', 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($this->structureMock), 'add'
        );
        $method->setAccessible(true);
        $method->invokeArgs(
            $this->structureMock,
            array(
                new Property('title', 'text_line')
            )
        );
        $method->invokeArgs(
            $this->structureMock,
            array(
                new Property('tags', 'text_line', false, false, 2, 10)
            )
        );
        $method->invokeArgs(
            $this->structureMock,
            array(
                new Property('url', 'resource_locator')
            )
        );
        $method->invokeArgs(
            $this->structureMock,
            array(
                new Property('article', 'text_area')
            )
        );

        $this->structureFactoryMock = $this->getMock('\Sulu\Component\Content\StructureManagerInterface');
        $this->structureFactoryMock->expects($this->any())
            ->method('getStructure')
            ->will($this->returnValue($this->structureMock));

        $containerMock = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $containerMock->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(array($this, 'containerCallback'))
            );

        return $containerMock;
    }

    public function containerCallback()
    {
        $resourceLocator = new ResourceLocator($this->sessionService, 'not in use');

        $result = array(
            'sulu.phpcr.session' => $this->sessionService,
            'sulu.content.structure_manager' => $this->structureFactoryMock,
            'sulu.content.type.text_line' => new TextLine('not in use'),
            'sulu.content.type.text_area' => new TextArea('not in use'),
            'sulu.content.type.resource_locator' => $resourceLocator
        );
        $args = func_get_args();

        return $result[$args[0]];
    }

    private function prepareSession()
    {
        $factoryclass = '\Jackalope\RepositoryFactoryJackrabbit';
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new $factoryclass();
        $repository = $factory->getRepository($parameters);
        $credentials = new \PHPCR\SimpleCredentials('admin', 'admin');
        $this->session = $repository->login($credentials, 'default');
    }

    public function tearDown()
    {
        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();
    }

    public function testSave()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/de/test',
            'article' => 'Test'
        );

        $this->mapper->save($data, 'de', 'overview');

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/routes/de/test');

        $content = $route->getPropertyValue('content');

        $this->assertEquals('Testtitle', $content->getProperty('title')->getString());
        $this->assertEquals('Test', $content->getProperty('article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('tags'));
    }

    public function testRead()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/de/test',
            'article' => 'Test'
        );

        $this->mapper->save($data, 'overview');

        $content = $this->mapper->read('/Testtitle', 'de', 'overview');

        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('Test', $content->article);
        $this->assertEquals('/de/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
    }
}
