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
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentTwigExtension;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentTwigExtensionTest extends TestCase
{
    /**
     * @var StructureResolverInterface|ObjectProphecy
     */
    private $structureResolver;

    /**
     * @var ContentMapperInterface|ObjectProphecy
     */
    private $contentMapper;

    /**
     * @var RequestAnalyzerInterface|ObjectProphecy
     */
    private $requestAnalyzer;

    /**
     * @var SessionManagerInterface|ObjectProphecy
     */
    private $sessionManager;

    /**
     * @var SessionInterface|ObjectProphecy
     */
    private $session;

    /**
     * @var NodeInterface|ObjectProphecy
     */
    private $node;

    /**
     * @var NodeInterface|ObjectProphecy
     */
    private $parentNode;

    /**
     * @var NodeInterface|ObjectProphecy
     */
    private $startPageNode;

    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    /**
     * @var ContentTwigExtension
     */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->startPageNode = $this->prophesize(NodeInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $webspace = new Webspace();
        $webspace->setKey('sulu_test');

        $locale = new Localization();
        $locale->setCountry('us');
        $locale->setLanguage('en');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($locale);

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

        $this->extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal()
        );
    }

    public function testLoadWithoutProperties()
    {
        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];
        $this->structureResolver->resolve($testStructure->reveal())->willReturn($resolvedStructure);

        $result = $this->extension->load('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadWithRequestStack()
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            $requestStack->reveal()
        );

        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];

        $currentRequest = $this->prophesize(Request::class);
        $requestStack->getCurrentRequest()->willReturn($currentRequest->reveal());
        $subRequest = $this->prophesize(Request::class);
        $currentRequest->duplicate([], [], null, null, [])->willReturn($subRequest->reveal());
        $requestStack->push($subRequest)->shouldBeCalled();

        $this->structureResolver->resolve($testStructure->reveal())->willReturn($resolvedStructure);

        $requestStack->pop()->shouldBeCalled();

        $result = $extension->load('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadWithRequestStackAndException()
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver->reveal(),
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->logger->reveal(),
            $requestStack->reveal()
        );

        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $currentRequest = $this->prophesize(Request::class);
        $requestStack->getCurrentRequest()->willReturn($currentRequest->reveal());
        $subRequest = $this->prophesize(Request::class);
        $currentRequest->duplicate([], [], null, null, [])->willReturn($subRequest->reveal());
        $requestStack->push($subRequest)->shouldBeCalled();

        $this->structureResolver->resolve($testStructure->reveal())->willThrow(new \RuntimeException());

        $requestStack->pop()->shouldBeCalled();

        $this->expectException(\RuntimeException::class);
        $extension->load('123-123-123');
    }

    public function testLoadWithProperties()
    {
        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            false,
            ['property-1', 'invalid-property-name']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
        ]);

        $result = $this->extension->load('123-123-123', ['property-1', 'invalid-property-name']);

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'property-1' => 'view',
                ],
                'content' => [
                    'property-1' => 'content',
                ],
            ],
            $result
        );
    }

    public function testLoadWithPropertiesWithKeys()
    {
        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            false,
            ['property-1', 'invalid-property-name']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
        ]);

        $result = $this->extension->load(
            '123-123-123',
            ['myTemplateProperty' => 'property-1', 'invalidProperty' => 'invalid-property-name']
        );

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'myTemplateProperty' => 'view',
                ],
                'content' => [
                    'myTemplateProperty' => 'content',
                ],
            ],
            $result
        );
    }

    public function testLoadWithPropertiesIncludingExcerpt()
    {
        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('123-123-123', 'sulu_test', 'en_us')
            ->willReturn($testStructure->reveal());

        $this->structureResolver->resolve(
            $testStructure->reveal(),
            true,
            ['property-1']
        )->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
            'extension' => [
                'excerpt' => ['title' => 'test-title', 'description' => 'test-description'],
            ],
        ]);

        $result = $this->extension->load(
            '123-123-123',
            ['myTemplateProperty' => 'property-1', 'excerptTitle' => 'excerpt.title']
        );

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'myTemplateProperty' => 'view',
                    'excerptTitle' => [],
                ],
                'content' => [
                    'myTemplateProperty' => 'content',
                    'excerptTitle' => 'test-title',
                ],
            ],
            $result
        );
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

    public function testLoadParentWithoutProperties()
    {
        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('321-321-321', 'sulu_test', 'en_us')
            ->willReturn($testStructure);

        $resolvedStructure = [
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
                'property-2' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
                'property-2' => 'content',
            ],
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
        ];
        $this->structureResolver->resolve($testStructure->reveal())->willReturn($resolvedStructure);

        $result = $this->extension->loadParent('123-123-123');

        $this->assertSame($resolvedStructure, $result);
    }

    public function testLoadParentWithProperties()
    {
        $testStructure = $this->prophesize(StructureBridge::class);

        $this->contentMapper->load('321-321-321', 'sulu_test', 'en_us')
            ->willReturn($testStructure);

        $this->structureResolver->resolve($testStructure->reveal(), true, ['property-1'])->willReturn([
            'id' => 'some-uuid',
            'template' => 'test',
            'view' => [
                'property-1' => 'view',
            ],
            'content' => [
                'property-1' => 'content',
            ],
            'extension' => [
                'excerpt' => ['title' => 'test-title', 'description' => 'test-description'],
            ],
        ]);

        $result = $this->extension->loadParent(
            '123-123-123',
            ['myTemplateProperty' => 'property-1', 'excerptTitle' => 'excerpt.title']
        );

        $this->assertSame(
            [
                'id' => 'some-uuid',
                'template' => 'test',
                'view' => [
                    'myTemplateProperty' => 'view',
                    'excerptTitle' => [],
                ],
                'content' => [
                    'myTemplateProperty' => 'content',
                    'excerptTitle' => 'test-title',
                ],
            ],
            $result
        );
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
