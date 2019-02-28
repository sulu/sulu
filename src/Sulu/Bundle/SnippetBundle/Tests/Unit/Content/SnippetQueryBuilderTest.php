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
use Sulu\Bundle\SnippetBundle\Content\SnippetQueryBuilder;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class SnippetQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SnippetQueryBuilder
     */
    private $snippetQueryBuilder;

    public function setUp()
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

    public function testBuild()
    {
        list($sql2) = $this->snippetQueryBuilder->build('sulu_io', ['de']);

        $this->assertContains('page.[jcr:mixinTypes] = "sulu:snippet"', $sql2);
    }

    public function testBuildWithTypes()
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

        $this->assertContains('(ISDESCENDANTNODE(page, \'/cmf/snippets/default\')', $sql2);
    }

    public function testBuildWithProperties()
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
