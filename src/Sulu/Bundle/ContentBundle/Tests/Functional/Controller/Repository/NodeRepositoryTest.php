<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Controller\Repository;

use Jackalope\RepositoryFactoryJackrabbit;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;
use PHPCR\Util\NodeHelper;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Controller\Repository\GetContactInterface;
use Sulu\Bundle\ContentBundle\Controller\Repository\NodeRepository;
use Sulu\Bundle\ContentBundle\Controller\Repository\NodeRepositoryInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryInterface;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class NodeRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GetContactInterface
     */
    private $contactMock;
    /**
     * @var ContentMapperInterface
     */
    private $mapper;
    /**
     * @var ContainerInterface
     */
    private $containerMock;
    /**
     * @var NodeRepositoryInterface
     */
    private $nodeRepository;
    /**
     * @var SessionFactoryInterface
     */
    private $sessionService;
    /**
     * @var SecurityContextInterface
     */
    private $securityContextMock;
    /**
     * @var ContentTypeInterface
     */
    private $textArea;
    /**
     * @var ContentTypeInterface
     */
    private $textLine;
    /**
     * @var ContentTypeInterface
     */
    private $resourceLocator;
    /**
     * @var StructureManagerInterface
     */
    private $structureManagerMock;
    /**
     * @var SessionInterface
     */
    private $session;

    private function prepareGetTestData()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'Test'
        );

        return $this->mapper->save($data, 'overview', 'default', 'de', 1);
    }

    public function testGet()
    {
        $structure = $this->prepareGetTestData();

        $result = $this->nodeRepository->getNode($structure->getUuid(), 'default', 'en');

        $this->assertEquals($structure->getProperty('title')->getValue(), $result['title']);
        $this->assertEquals($structure->getProperty('url')->getValue(), $result['url']);
    }

    public function testDelete()
    {
        $structure = $this->prepareGetTestData();

        $this->nodeRepository->deleteNode($structure->getUuid(), 'default');

        $this->setExpectedException('PHPCR\ItemNotFoundException');
        $this->nodeRepository->getNode($structure->getUuid(), 'default', 'en');
    }

    protected function setUp()
    {
        $this->prepareContainerMock();
        $this->prepareContactMock();

        $this->prepareMapper();

        $this->nodeRepository = new NodeRepository($this->mapper, $this->contactMock);
    }

    private function prepareContainerMock()
    {
        $this->prepareServices();

        $this->sessionService = new SessionFactoryService(new RepositoryFactoryJackrabbit(), array(
            'url' => 'http://localhost:8080/server',
            'username' => 'admin',
            'password' => 'admin',
            'workspace' => 'default'
        ));

        $this->containerMock = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $this->containerMock
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(array($this, 'containerCallback'))
            );
    }

    private function prepareServices()
    {
        $this->textArea = new TextArea('not in use');
        $this->textLine = new TextLine('not in use');

        $this->prepareSecurityMock();
        $this->prepareStructureMock();
    }

    private function prepareSecurityMock()
    {
        $userMock = $this->getMock('\Sulu\Component\Security\UserInterface');
        $userMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $tokenMock = $this->getMock('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $tokenMock->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($userMock));

        $this->securityContextMock = $this->getMock('\Symfony\Component\Security\Core\SecurityContextInterface');
        $this->securityContextMock->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($tokenMock));
    }

    public function prepareStructureMock()
    {
        $this->structureManagerMock = $this->getMock('\Sulu\Component\Content\StructureManagerInterface');
        $this->structureManagerMock->expects($this->any())
            ->method('getStructure')
            ->will($this->returnCallback(array($this, 'getStructureCallback')));
    }

    private function prepareMapper()
    {
        $this->mapper = new ContentMapper('/cmf/contents', '/cmf/routes');
        $this->mapper->setContainer($this->containerMock);

        $this->prepareSession();
        $this->prepareRepository();

        $this->resourceLocator = new ResourceLocator(new TreeStrategy(new PhpcrMapper($this->sessionService, '/cmf/routes')), 'not in use');
    }

    private function prepareSession()
    {
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new RepositoryFactoryJackrabbit();
        $repository = $factory->getRepository($parameters);
        $credentials = new SimpleCredentials('admin', 'admin');
        $this->session = $repository->login($credentials, 'default');
    }

    public function prepareRepository()
    {
        $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace('sulu', 'http://sulu.io/phpcr');
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new SuluNodeType(), true);
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new PathNodeType(), true);
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new ContentNodeType(), true);
        $this->session->save();

        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();

        $cmf = $this->session->getRootNode()->addNode('cmf');
        $cmf->addMixin('mix:referenceable');

        $routes = $cmf->addNode('routes');
        $routes->addMixin('mix:referenceable');

        $contents = $cmf->addNode('contents');
        $contents->addMixin('mix:referenceable');
        $this->session->save();
    }

    private function prepareContactMock()
    {
        $this->contactMock = $this->getMock(
            '\Sulu\Bundle\ContentBundle\Controller\Repository\GetContactInterface',
            array('getContact')
        );
        $this->contactMock
            ->expects($this->any())
            ->method('getContact')
            ->will($this->returnValue('FirstName LastName'));
    }

    public function containerCallback()
    {
        $args = func_get_args();
        switch ($args[0]) {
            case 'sulu.phpcr.session':
                return $this->sessionService;
                break;
            case 'sulu.content.structure_manager':
                return $this->structureManagerMock;
                break;
            case 'sulu.content.type.text_area':
                return $this->textArea;
                break;
            case 'sulu.content.type.text_line':
                return $this->textLine;
                break;
            case 'sulu.content.type.resource_locator':
                return $this->resourceLocator;
                break;
            case 'security.context':
                return $this->securityContextMock;
                break;
        }

        return null;
    }

    public function getStructureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey == 'overview') {
            return $this->getStructureMock(1);
        } elseif ($structureKey == 'simple') {
            return $this->getStructureMock(2);
        }

        return null;
    }

    private function getStructureMock($type = 1)
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

        return $structureMock;
    }

}
