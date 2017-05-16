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

use Sulu\Bundle\SnippetBundle\Content\SnippetQueryBuilder;
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
     * @var SnippetQueryBuilder
     */
    private $snippetQueryBuilder;

    public function setUp()
    {
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);

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
                'types' => ['default', 'test'],
            ],
        ]);

        list($sql2) = $this->snippetQueryBuilder->build('sulu_io', ['de']);

        $this->assertContains('(page.[template] = "default" OR page.[template] = "test"))', $sql2);
    }
}
