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

use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\StructureBridge;

class SnippetContentTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContentData()
    {
        $defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $snippetResolver = $this->prophesize(SnippetResolverInterface::class);

        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en')
            ->willReturn(
                [['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']]]
            );

        $type = new SnippetContent($defaultSnippetManager->reveal(), $snippetResolver->reveal(), false, '');

        $result = $type->getContentData($property->reveal());

        $this->assertEquals([['title' => 'test-1']], $result);
    }

    public function testGetViewData()
    {
        $defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $snippetResolver = $this->prophesize(SnippetResolverInterface::class);

        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en')
            ->willReturn(
                [['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']]]
            );

        $type = new SnippetContent($defaultSnippetManager->reveal(), $snippetResolver->reveal(), false, '');

        $result = $type->getViewData($property->reveal());

        $this->assertEquals([['title' => 'test-2', 'template' => 'default']], $result);
    }
}
