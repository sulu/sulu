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

use Jackalope\RepositoryFactoryJackrabbit;
use Jackalope\Session;
use PHPCR\SimpleCredentials;
use PHPCR\Util\NodeHelper;
use ReflectionMethod;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryService;

class ContentMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionFactoryService
     */
    public $sessionService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    public $container;

    /**
     * @var ContentMapper
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getContainerMock()
    {
        $this->sessionService = new SessionFactoryService(new RepositoryFactoryJackrabbit(), array(
            'url' => 'http://localhost:8080/server',
            'username' => 'admin',
            'password' => 'admin',
            'workspace' => 'default'
        ));

        $containerMock = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $containerMock->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(array($this, 'containerCallback'))
            );

        return $containerMock;
    }

    public function getStrucktureManager(){


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
                new Property('tags', 'text_line', false, false, 2, 10)
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

        $structureManagerMock = $this->getMock('\Sulu\Component\Content\StructureManagerInterface');
        $structureManagerMock->expects($this->any())
            ->method('getStructure')
            ->will($this->returnValue($structureMock));

        return $structureManagerMock;
    }

    public function containerCallback()
    {
        $resourceLocator = new ResourceLocator($this->sessionService, 'not in use', '/cmf/routes');

        $result = array(
            'sulu.phpcr.session' => $this->sessionService,
            'sulu.content.structure_manager' => $this->getStrucktureManager(),
            'sulu.content.type.text_line' => new TextLine('not in use'),
            'sulu.content.type.text_area' => new TextArea('not in use'),
            'sulu.content.type.resource_locator' => $resourceLocator
        );
        $args = func_get_args();

        return $result[$args[0]];
    }

    private function prepareSession()
    {
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new RepositoryFactoryJackrabbit();
        $repository = $factory->getRepository($parameters);
        $credentials = new SimpleCredentials('admin', 'admin');
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
        $this->assertEquals('/Testtitle', $content->path);
    }
}
