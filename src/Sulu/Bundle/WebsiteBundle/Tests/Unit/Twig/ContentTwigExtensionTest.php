<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Twig;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentTwigExtension;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;

class ContentTwigExtensionTest extends TestCase
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var NodeInterface
     */
    private $startPageNode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContentTwigExtension
     */
    private $extension;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->startPageNode = $this->prophesize(NodeInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->webspace = new Webspace();
        $this->webspace->setKey('sulu_test');

        $locale = new Localization();
        $locale->setCountry('us');
        $locale->setLanguage('en');

        $this->webspaceManager->findWebspaceByKey('sulu_test')->willReturn($this->webspace);

        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($locale);

        $this->contentTypeManager->get('text_line')->willReturn(new TextLine(''));

        $this->sessionManager->getSession()->willReturn($this->session->reveal());
        $this->sessionManager->getContentNode('sulu_test')->willReturn($this->startPageNode->reveal());

        $this->session->getNodeByIdentifier('123-123-123')->willReturn($this->node->reveal());
        $this->session->getNodeByIdentifier('321-321-321')->willReturn($this->parentNode->reveal());

        $this->node->getIdentifier()->willReturn('123-123-123');
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->node->getDepth()->willReturn(4);

        $this->parentNode->getIdentifier()->willReturn('321-321-321');
        $this->parentNode->getDepth()->willReturn(3);

        $this->startPageNode->getDepth()->willReturn(3);

        $this->structureResolver = new StructureResolver(
            $this->contentTypeManager->reveal(),
            $this->extensionManager->reveal()
        );

        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(true);

        $this->extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver,
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            null,
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    public function testLoad()
    {
        $testStructure = $this->prophesize(StructureBridge::class);
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $testStructure->getKey()->willReturn('test');
        $testStructure->getPath()->willReturn(null);
        $testStructure->getUuid()->willReturn('123-123-123');
        $testStructure->getCreator()->willReturn(1);
        $testStructure->getChanger()->willReturn(1);
        $testStructure->getCreated()->willReturn(null);
        $testStructure->getChanged()->willReturn(null);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $titleProperty = new Property('title', [], 'text_line');
        $titleProperty->setValue('test');
        $testStructure->getProperties(true)->willReturn([$titleProperty]);

        $this
            ->contentMapper
            ->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure);

        $result = $this->extension->load('123-123-123');

        // uuid
        $this->assertEquals('123-123-123', $result['uuid']);

        // metadata
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        // content
        $this->assertEquals(['title' => 'test'], $result['content']);
        $this->assertEquals(['title' => []], $result['view']);
    }

    public function testLoadWithPermissions()
    {
        $testStructure = $this->prophesize(StructureBridge::class);
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $testStructure->getKey()->willReturn('test');
        $testStructure->getPath()->willReturn(null);
        $testStructure->getUuid()->willReturn('123-123-123');
        $testStructure->getCreator()->willReturn(1);
        $testStructure->getChanger()->willReturn(1);
        $testStructure->getCreated()->willReturn(null);
        $testStructure->getChanged()->willReturn(null);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $titleProperty = new Property('title', [], 'text_line');
        $titleProperty->setValue('test');
        $testStructure->getProperties(true)->willReturn([$titleProperty]);

        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(false);

        $this
            ->contentMapper
            ->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure);

        $result = $this->extension->load('123-123-123');

        $this->assertNotNull($result);
    }

    public function testLoadWithoutPermissions()
    {
        $testStructure = $this->prophesize(StructureBridge::class);
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $testStructure->getKey()->willReturn('test');
        $testStructure->getPath()->willReturn(null);
        $testStructure->getUuid()->willReturn('123-123-123');
        $testStructure->getCreator()->willReturn(1);
        $testStructure->getChanger()->willReturn(1);
        $testStructure->getCreated()->willReturn(null);
        $testStructure->getChanged()->willReturn(null);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $titleProperty = new Property('title', [], 'text_line');
        $titleProperty->setValue('test');
        $testStructure->getProperties(true)->willReturn([$titleProperty]);

        $this->securityChecker->hasPermission(
            new SecurityCondition(
                PageAdmin::SECURITY_CONTEXT_PREFIX . 'sulu_test',
                'en_us',
                SecurityBehavior::class,
                '123-123-123',
                'sulu_test'
            ),
            PermissionTypes::VIEW
        )->willReturn(false);

        $security = new Security();
        $security->setSystem('sulu_test');
        $security->setPermissionCheck(true);
        $this->webspace->setSecurity($security);

        $this
            ->contentMapper
            ->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure);

        $result = $this->extension->load('123-123-123');

        $this->assertEquals(null, $result);
    }

    public function testLoadNull()
    {
        $this
            ->contentMapper
            ->load(Argument::cetera())
            ->shouldNotBeCalled();

        $this->assertNull($this->extension->load(null));
    }

    public function testLoadNotExistingDocument()
    {
        $documentNotFoundException = $this->prophesize(DocumentNotFoundException::class);
        $documentNotFoundException->__toString()->willReturn('something');

        $this
            ->contentMapper
            ->load(Argument::cetera())
            ->willThrow($documentNotFoundException->reveal());

        $this->assertNull($this->extension->load('999-999-999'));
    }

    public function testLoadParent()
    {
        $testStructure = $this->prophesize(StructureBridge::class);
        $pageDocument = $this->prophesize(WebspaceBehavior::class);
        $testStructure->getKey()->willReturn('test');
        $testStructure->getPath()->willReturn(null);
        $testStructure->getUuid()->willReturn('321-321-321');
        $testStructure->getCreator()->willReturn(1);
        $testStructure->getChanger()->willReturn(1);
        $testStructure->getCreated()->willReturn(null);
        $testStructure->getChanged()->willReturn(null);
        $testStructure->getDocument()->willReturn($pageDocument);
        $testStructure->getWebspaceKey()->willReturn('sulu_test');

        $titleProperty = new Property('title', [], 'text_line');
        $titleProperty->setValue('test');
        $testStructure->getProperties(true)->willReturn([$titleProperty]);

        $this
            ->contentMapper
            ->load('321-321-321', 'sulu_test', 'en_us')
            ->willReturn($testStructure);

        $result = $this->extension->loadParent('123-123-123');

        // uuid
        $this->assertEquals('321-321-321', $result['uuid']);

        // metadata
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        // content
        $this->assertEquals(['title' => 'test'], $result['content']);
        $this->assertEquals(['title' => []], $result['view']);
    }

    public function testLoadWithoutWebspaceBehaviorDocument()
    {
        $testStructure = $this->prophesize(SnippetBridge::class);
        $snippetDocument = $this->prophesize(StructureBehavior::class);
        $testStructure->getKey()->willReturn('test');
        $testStructure->getPath()->willReturn(null);
        $testStructure->getUuid()->willReturn('123-123-123');
        $testStructure->getCreator()->willReturn(1);
        $testStructure->getChanger()->willReturn(1);
        $testStructure->getCreated()->willReturn(null);
        $testStructure->getChanged()->willReturn(null);
        $testStructure->getDocument()->willReturn($snippetDocument);

        $titleProperty = new Property('title', [], 'text_line');
        $titleProperty->setValue('test');
        $testStructure->getProperties(true)->willReturn([$titleProperty]);

        $this
            ->contentMapper
            ->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure);

        $result = $this->extension->load('123-123-123');

        // uuid
        $this->assertEquals('123-123-123', $result['uuid']);

        // metadata
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        // content
        $this->assertEquals(['title' => 'test'], $result['content']);
        $this->assertEquals(['title' => []], $result['view']);
    }

    public function testLoadParentStartPage()
    {
        $this->expectException(
            'Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException',
            'Parent for "321-321-321" not found (perhaps it is the startpage?)'
        );

        $this->extension->loadParent('321-321-321');
    }
}
