<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Content;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SnippetBundle\Content\SnippetQueryBuilder;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class SnippetQueryBuilderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var ObjectProphecy<ExtensionManagerInterface>
     */
    private $extensionManager;

    /**
     * @var ObjectProphecy<SessionManagerInterface>
     */
    private $sessionManager;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $session;

    /**
     * @var SnippetQueryBuilder
     */
    private $snippetQueryBuilder;

    public function setUp(): void
    {
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);

        $this->sessionManager->getSession()->willReturn($this->session->reveal());

        $this->snippetQueryBuilder = new SnippetQueryBuilder(
            $this->structureManager->reveal(),
            $this->extensionManager->reveal(),
            $this->sessionManager->reveal(),
            'i18n'
        );
    }

    public function testBuild(): void
    {
        list($sql2) = $this->snippetQueryBuilder->build('sulu_io', ['de']);

        $this->assertStringContainsString('page.[jcr:mixinTypes] = "sulu:snippet"', $sql2);
    }

    public function testBuildWithTypes(): void
    {
        $this->snippetQueryBuilder->init([
            'config' => [
                'dataSource' => 'some-uuid',
                'includeSubFolders' => true,
            ],
        ]);

        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn('/cmf/snippets/default');
        $this->session->getNodeByIdentifier('some-uuid')->willReturn($node->reveal());

        list($sql2) = $this->snippetQueryBuilder->build('sulu_io', ['de']);

        $this->assertStringContainsString('(ISDESCENDANTNODE(page, \'/cmf/snippets/default\')', $sql2);
    }

    public function testBuildWithProperties(): void
    {
        $this->structureManager->getStructures(Structure::TYPE_SNIPPET)->shouldBeCalled()->willReturn([]);

        $this->snippetQueryBuilder->init([
            'properties' => [
                new PropertyParameter('description', 'description'),
            ],
        ]);

        list($sql2) = $this->snippetQueryBuilder->build('sulu_io', ['de']);
    }
}
