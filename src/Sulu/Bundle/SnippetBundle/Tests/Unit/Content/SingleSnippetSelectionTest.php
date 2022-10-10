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

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SnippetBundle\Content\SingleSnippetSelection;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure\StructureBridge;

class SingleSnippetSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DefaultSnippetManagerInterface>
     */
    private $defaultSnippetManager;

    /**
     * @var ObjectProphecy<SnippetResolverInterface>
     */
    private $snippetResolver;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    /**
     * @var SingleSnippetSelection
     */
    private $singleSnippetSelection;

    protected function setUp(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->singleSnippetSelection = new SingleSnippetSelection(
            $this->snippetResolver->reveal(),
            $this->defaultSnippetManager->reveal(),
            $this->referenceStore->reveal()
        );
    }

    public function testGetContentData(): void
    {
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn('123-123-123');
        $property->getParams()->willReturn([]);

        $this->snippetResolver
            ->resolve(['123-123-123'], 'sulu_io', 'de', 'en', false)
            ->willReturn([
                ['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']],
            ]);

        $result = $this->singleSnippetSelection->getContentData($property->reveal());

        $this->assertEquals(['title' => 'test-1'], $result);
    }

    public function testGetContentDataNullValue(): void
    {
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(null);
        $property->getParams()->willReturn([]);

        $result = $this->singleSnippetSelection->getContentData($property->reveal());

        $this->assertEquals(null, $result);
    }

    public function testGetContentDataFallbackToSnippetArea(): void
    {
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(null);
        $property->getParams()->willReturn(['default' => new PropertyParameter('default', 'footer-snippet')]);

        $defaultSnippet = $this->prophesize(SnippetDocument::class);
        $defaultSnippet->getUuid()->willReturn('456-456-456');
        $this->defaultSnippetManager->load('sulu_io', 'footer-snippet', 'de')->willReturn($defaultSnippet->reveal());

        $this->snippetResolver
            ->resolve(['456-456-456'], 'sulu_io', 'de', null, false)
            ->willReturn([
                ['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']],
            ]);

        $result = $this->singleSnippetSelection->getContentData($property->reveal());

        $this->assertEquals(['title' => 'test-1'], $result);
    }

    public function testGetContentDataWithExtensions(): void
    {
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn('123-123-123');
        $property->getParams()->willReturn(['loadExcerpt' => new PropertyParameter('loadExcerpt', true)]);

        $this->snippetResolver
            ->resolve(['123-123-123'], 'sulu_io', 'de', null, true)
            ->willReturn(
                [
                    [
                        'content' => ['title' => 'test-1', 'taxonomies' => ['categories' => [], 'tags' => []]],
                        'view' => ['title' => 'test-2', 'template' => 'default'],
                    ],
                ]
            );

        $result = $this->singleSnippetSelection->getContentData($property->reveal());

        $this->assertEquals(['title' => 'test-1', 'taxonomies' => ['categories' => [], 'tags' => []]], $result);
    }

    public function testGetViewData(): void
    {
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn('123-123-123');
        $property->getParams()->willReturn([]);

        $this->snippetResolver
            ->resolve(['123-123-123'], 'sulu_io', 'de', 'en', false)
            ->willReturn([
                ['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']],
            ]);

        $result = $this->singleSnippetSelection->getViewData($property->reveal());

        $this->assertEquals(['title' => 'test-2', 'template' => 'default'], $result);
    }

    public function testGetViewDataNullValue(): void
    {
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(null);
        $property->getParams()->willReturn([]);

        $result = $this->singleSnippetSelection->getViewData($property->reveal());

        $this->assertEquals([], $result);
    }

    public function testPreResolve(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn('123-123-123');

        $this->singleSnippetSelection->preResolve($property->reveal());

        $this->referenceStore->add('123-123-123')->shouldBeCalled();
    }

    public function testPreResolveNullValue(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(null);

        $this->singleSnippetSelection->preResolve($property->reveal());

        $this->referenceStore->add(Argument::cetera())->shouldNotBeCalled();
    }
}
