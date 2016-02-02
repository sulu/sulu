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
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\Compat\Structure\StructureBridge;

class SnippetContentTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContentData()
    {
        $defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $structureResolver = $this->prophesize(SnippetResolverInterface::class);

        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);

        $referenceDe = $this->prophesize(SnippetBridge::class);
        $referenceEn = $this->prophesize(SnippetBridge::class);

        $referenceDe->getHasTranslation()->willReturn(false);
        $referenceEn->getHasTranslation()->willReturn(true);

        $defaultSnippetManager->load('123-123-123', 'sulu_io', 'de')->willReturn($referenceDe->reveal());
        $defaultSnippetManager->load('123-123-123', 'sulu_io', 'en')->willReturn($referenceEn->reveal());

        $referenceEn->setIsShadow(true)->shouldBeCalled();
        $referenceEn->setShadowBaseLanguage('en')->shouldBeCalled();
        $referenceEn->getKey()->willReturn('default');

        $structureResolver->resolve($referenceEn)->willReturn(
            ['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2']]
        );

        $type = new SnippetContent($defaultSnippetManager->reveal(), $structureResolver->reveal(), false, '');

        $result = $type->getContentData($property->reveal());

        self::assertEquals([['title' => 'test-1']], $result);
    }

    public function testGetViewData()
    {
        $defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $structureResolver = $this->prophesize(SnippetResolverInterface::class);

        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);

        $referenceDe = $this->prophesize(SnippetBridge::class);
        $referenceEn = $this->prophesize(SnippetBridge::class);

        $referenceDe->getHasTranslation()->willReturn(false);
        $referenceEn->getHasTranslation()->willReturn(true);

        $defaultSnippetManager->load('123-123-123', 'sulu_io', 'de')->willReturn($referenceDe->reveal());
        $defaultSnippetManager->load('123-123-123', 'sulu_io', 'en')->willReturn($referenceEn->reveal());

        $referenceEn->setIsShadow(true)->shouldBeCalled();
        $referenceEn->setShadowBaseLanguage('en')->shouldBeCalled();
        $referenceEn->getKey()->willReturn('default');

        $structureResolver->resolve($referenceEn)->willReturn(
            ['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2']]
        );

        $type = new SnippetContent($defaultSnippetManager->reveal(), $structureResolver->reveal(), false, '');

        $result = $type->getViewData($property->reveal());

        self::assertEquals([['title' => 'test-2', 'template' => 'default']], $result);
    }
}
