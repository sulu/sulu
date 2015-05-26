<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use DateTime;
use Jackalope\RepositoryFactoryJackrabbit;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;
use PHPCR\Util\NodeHelper;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolver;
use Sulu\Component\Content\Block\BlockContentType;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface;
use Sulu\Component\Content\Mapper\LocalizationFinder\ParentChildAnyFinder;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Template\TemplateResolver;
use Sulu\Component\Content\Template\TemplateResolverInterface;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\PageNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\SnippetNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Component\PHPCR\PathCleanup;
use Sulu\Component\PHPCR\SessionManager\SessionManager;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * prepares repository and basic classes for phpcr test cases.
 */
abstract class PhpcrTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var NodeInterface
     */
    protected $contents;

    /**
     * @var NodeInterface
     */
    protected $routes;

    /**
     * @var array
     */
    protected $languageRoutes;

    /**
     * @var ContentMapperInterface
     */
    protected $mapper;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $containerValueMap = array();

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var NodeInterface[]
     */
    protected $structureValueMap = array();

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var LocalizationFinderInterface
     */
    protected $localizationFinder;

    /**
     * @var TemplateResolverInterface
     */
    protected $templateResolver;

    /**
     * @var SuluNodeHelper
     */
    protected $nodeHelper;

    /**
     * The default language for the content mapper.
     *
     * @var string
     */
    protected $language = 'de';

    /**
     * The default template for the content mapper.
     *
     * @var string
     */
    protected $defaultTemplates = array('page' => 'default', 'snippet' => 'default');

    /**
     * The language namespace.
     *
     * @var string
     */
    protected $languageNamespace = 'i18n';

    /**
     * The language namespace.
     *
     * @var string
     */
    protected $internalPrefix = '';

    /**
     * purge webspace at tear down.
     */
    public function tearDown()
    {
        if (isset($this->session)) {
            NodeHelper::purgeWorkspace($this->session);
            $this->session->save();
        }
    }

    /**
     * prepares a content mapper.
     */
    protected function prepareMapper()
    {
        if ($this->mapper === null) {
            $this->prepareContainer();

            $this->prepareSession();
            $this->prepareRepository();

            $this->prepareContentTypeManager();
            $this->prepareStructureManager();
            $this->prepareSecurityContext();
            $this->prepareSessionManager();
            $this->prepareWebspaceManager();
            $this->prepareEventDispatcher();
            $this->prepareLocalizationFinder();

            $this->templateResolver = new TemplateResolver();
            $this->nodeHelper = new SuluNodeHelper(
                $this->sessionManager->getSession(),
                'i18n',
                array(
                    'base' => 'cmf',
                    'content' => 'contents',
                    'route' => 'routes',
                    'snippet' => 'snippets',
                )
            );
            $cleaner = new PathCleanup();
            $strategy = new TreeStrategy(
                new PhpcrMapper($this->sessionManager, '/cmf/routes'),
                $cleaner,
                $this->structureManager,
                $this->contentTypeManager,
                $this->nodeHelper
            );

            $this->mapper = new ContentMapper(
                $this->contentTypeManager,
                $this->structureManager,
                $this->sessionManager,
                $this->eventDispatcher,
                $this->localizationFinder,
                $cleaner,
                $this->webspaceManager,
                $this->templateResolver,
                $this->nodeHelper,
                $strategy,
                $this->language,
                $this->defaultTemplates,
                $this->languageNamespace,
                $this->internalPrefix
            );

            $structureResolver = new StructureResolver($this->contentTypeManager, $this->structureManager);

            $snippet = new SnippetContent(
                $this->mapper,
                $structureResolver,
                'not in use'
            );

            $resourceLocator = new ResourceLocator($strategy, 'not in use');
            $this->containerValueMap = array_merge(
                $this->containerValueMap,
                array(
                    'sulu.phpcr.session' => $this->sessionManager,
                    'sulu.content.structure_manager' => $this->structureManager,
                    'sulu.content.type.text_line' => new TextLine('not in use'),
                    'sulu.content.type.text_area' => new TextArea('not in use'),
                    'sulu.content.type.resource_locator' => $resourceLocator,
                    'sulu_snippet.content.snippet' => $snippet,
                    'sulu.content.type.block' => new BlockContentType(
                            $this->contentTypeManager,
                            'not in use',
                            $this->languageNamespace
                        ),
                    'security.context' => $this->securityContext,
                )
            );
        }
    }

    protected function prepareContentTypeManager()
    {
        if ($this->contentTypeManager === null) {
            $this->contentTypeManager = new ContentTypeManager($this->container);
            $this->contentTypeManager->mapAliasToServiceId('text_line', 'sulu.content.type.text_line');
            $this->contentTypeManager->mapAliasToServiceId('text_area', 'sulu.content.type.text_area');
            $this->contentTypeManager->mapAliasToServiceId('resource_locator', 'sulu.content.type.resource_locator');
            $this->contentTypeManager->mapAliasToServiceId('block', 'sulu.content.type.block');
            $this->contentTypeManager->mapAliasToServiceId('snippet', 'sulu_snippet.content.snippet');
        }
    }

    /**
     * prepares event dispatcher manager.
     */
    protected function prepareEventDispatcher()
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        }
    }

    protected function prepareLocalizationFinder()
    {
        if ($this->localizationFinder === null) {
            $this->localizationFinder = new ParentChildAnyFinder(
                $this->webspaceManager,
                $this->languageNamespace,
                $this->internalPrefix
            );
        }
    }

    /**
     * prepares webspace manager.
     */
    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
        }
    }

    /**
     * prepares a structure manager.
     */
    protected function prepareStructureManager()
    {
        if ($this->structureManager === null) {
            $this->structureManager = $this->getMock('\Sulu\Component\Content\StructureManagerInterface');

            $this->structureManager->expects($this->any())
                ->method('getStructure')
                ->will($this->returnCallback(array($this, 'structureCallback')));

            $this->structureManager->expects($this->any())
                ->method('getExtensions')
                ->will($this->returnCallback(array($this, 'getExtensionsCallback')));

            $this->structureManager->expects($this->any())
                ->method('getExtension')
                ->will($this->returnCallback(array($this, 'getExtensionCallback')));
        }
    }

    /**
     * default get extension callback returns a empty array.
     *
     * @return array
     */
    public function getExtensionsCallback()
    {
        return array();
    }

    /**
     * default get extension callback returns null.
     *
     * @return array
     */
    public function getExtensionCallback()
    {
        return;
    }

    /**
     * provides a callback for structure manager mock: function getStructure.
     *
     * @return mixed
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function structureCallback()
    {
        $args = func_get_args();
        $id = $args[0];
        if (isset($this->structureValueMap[$id])) {
            return $this->structureValueMap[$id];
        } else {
            return;
        }
    }

    /**
     * prepares a security context.
     */
    protected function prepareSecurityContext()
    {
        if ($this->securityContext === null) {
            $userMock = $this->getMock('\Sulu\Component\Security\Authentication\UserInterface');
            $userMock->expects($this->any())
                ->method('getId')
                ->will($this->returnValue(1));

            $tokenMock = $this->getMock('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
            $tokenMock->expects($this->any())
                ->method('getUser')
                ->will($this->returnValue($userMock));

            $this->securityContext = $this->getMock('\Symfony\Component\Security\Core\SecurityContextInterface');
            $this->securityContext->expects($this->any())
                ->method('getToken')
                ->will($this->returnValue($tokenMock));
        }
    }

    /**
     * prepares a session manager.
     */
    protected function prepareSessionManager()
    {
        if ($this->sessionManager === null) {
            $this->sessionManager = new SessionManager(
                $this->session,
                array(
                    'base' => 'cmf',
                    'route' => 'routes',
                    'content' => 'contents',
                    'snippet' => 'snippets',
                )
            );
        }
    }

    /**
     * prepares a container.
     */
    protected function prepareContainer()
    {
        if ($this->container === null) {
            $this->container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
            $this->container->expects($this->any())
                ->method('get')
                ->will(
                    $this->returnCallback(array($this, 'containerCallback'))
                );
        }
    }

    /**
     * provides a callback for container mock: function get.
     *
     * @return mixed
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function containerCallback()
    {
        $args = func_get_args();
        $id = $args[0];
        if (isset($this->containerValueMap[$id])) {
            return $this->containerValueMap[$id];
        } else {
            throw new ServiceNotFoundException($id);
        }
    }

    /**
     * prepares a session.
     */
    protected function prepareSession()
    {
        if ($this->session === null) {
            $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
            $factory = new RepositoryFactoryJackrabbit();
            $repository = $factory->getRepository($parameters);
            $credentials = new SimpleCredentials('admin', 'admin');
            $this->session = $repository->login($credentials, 'test');

            $this->prepareRepository();
        }
    }

    /**
     * prepares the repository.
     */
    protected function prepareRepository()
    {
        if ($this->contents === null) {
            $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace('sulu', 'http://sulu.io/phpcr');
            $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace(
                $this->languageNamespace,
                'http://sulu.io/phpcr/locale'
            );
            $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new SuluNodeType(), true);
            $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new PathNodeType(), true);
            $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new ContentNodeType(), true);
            $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new PageNodeType(), true);
            $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new SnippetNodeType(), true);

            NodeHelper::purgeWorkspace($this->session);
            $this->session->save();

            $cmf = $this->session->getRootNode()->addNode('cmf');
            $cmf->addMixin('mix:referenceable');
            $this->session->save();

            $snippetsNode = $cmf->addNode('snippets');
            $snippetsNode->addNode('default_snippet');

            $default = $cmf->addNode('default');
            $default->addMixin('mix:referenceable');
            $this->session->save();

            $this->contents = $default->addNode('contents');
            $this->contents->setProperty($this->languageNamespace . ':de-template', 'overview');
            $this->contents->setProperty($this->languageNamespace . ':en-template', 'overview');
            $this->contents->setProperty($this->languageNamespace . ':de-changer', 1);
            $this->contents->setProperty($this->languageNamespace . ':en-changer', 1);
            $this->contents->setProperty($this->languageNamespace . ':de-creator', 1);
            $this->contents->setProperty($this->languageNamespace . ':en-creator', 1);
            $this->contents->setProperty($this->languageNamespace . ':de-changed', new DateTime());
            $this->contents->setProperty($this->languageNamespace . ':en-changed', new DateTime());
            $this->contents->setProperty($this->languageNamespace . ':de-created', new DateTime());
            $this->contents->setProperty($this->languageNamespace . ':en-created', new DateTime());
            $this->contents->setProperty($this->languageNamespace . ':en-url', '/');
            $this->contents->addMixin('sulu:page');
            $this->session->save();

            $this->routes = $default->addNode('routes');
            $this->session->save();

            $dePath = $this->routes->addNode('de');
            $dePath->addMixin('sulu:path');
            $dePath->setProperty('sulu:content', $this->contents);
            $this->languageRoutes['de'] = $dePath;
            $this->session->save();

            $de_atPath = $this->routes->addNode('de_at');
            $de_atPath->setProperty('sulu:content', $this->contents);
            $de_atPath->addMixin('sulu:path');
            $this->languageRoutes['de_at'] = $de_atPath;
            $this->session->save();

            $enPath = $this->routes->addNode('en');
            $enPath->setProperty('sulu:content', $this->contents);
            $enPath->addMixin('sulu:path');
            $this->languageRoutes['en'] = $enPath;
            $this->session->save();

            $en_usPath = $this->routes->addNode('en_us');
            $en_usPath->setProperty('sulu:content', $this->contents);
            $en_usPath->addMixin('sulu:path');
            $this->languageRoutes['en_us'] = $en_usPath;
            $this->session->save();
        }
    }
}
