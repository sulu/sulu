<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Content;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\SnippetBundle\Content\SnippetQueryBuilder;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
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
     * @var PropertyEncoder
     */
    private $propertyEncoder;

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
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->sessionManager->getSession()->willReturn($this->session->reveal());

        $this->snippetQueryBuilder = new SnippetQueryBuilder(
            $this->structureManager->reveal(),
            $this->extensionManager->reveal(),
            $this->sessionManager->reveal(),
            $this->propertyEncoder->reveal(),
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
        $property = $this->prophesize(PropertyInterface::class);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getKey()->willReturn('test');
        $structure->hasProperty('description')->willReturn(true);
        $structure->getProperty('description')->willReturn($property->reveal());

        $this->structureManager->getStructures(Structure::TYPE_SNIPPET)->shouldBeCalled()->willReturn(
                [$structure->reveal()]
            );

        $this->snippetQueryBuilder->init([
            'properties' => [
                new PropertyParameter('description', 'description'),
            ],
        ]);

        list($sql2, $additionalFields) = $this->snippetQueryBuilder->build('sulu_io', ['de']);
        $this->assertContains('SELECT page.*', $sql2);
        $this->assertContains(
            ['name' => 'description', 'property' => $property->reveal(), 'templateKey' => 'test'],
            $additionalFields['de']
        );
    }
}
