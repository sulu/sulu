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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure\StructureBridge;

class SnippetContentTest extends TestCase
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
     * @var SnippetContent
     */
    private $contentType;

    protected function setUp(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->contentType = new SnippetContent(
            $this->defaultSnippetManager->reveal(),
            $this->snippetResolver->reveal(),
            $this->referenceStore->reveal(),
            false,
            ''
        );
    }

    public function testGetContentData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $this->snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en', false)
            ->willReturn(
                [['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']]]
            );

        $result = $this->contentType->getContentData($property->reveal());

        $this->assertEquals([['title' => 'test-1']], $result);
    }

    public function testGetContentDataWithExtensions(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn(['loadExcerpt' => new PropertyParameter('loadExcerpt', true)]);

        $this->snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en', true)
            ->willReturn(
                [
                    [
                        'content' => ['title' => 'test-1', 'taxonomies' => ['categories' => [], 'tags' => []]],
                        'view' => ['title' => 'test-2', 'template' => 'default'],
                    ],
                ]
            );

        $result = $this->contentType->getContentData($property->reveal());

        $this->assertEquals([['title' => 'test-1', 'taxonomies' => ['categories' => [], 'tags' => []]]], $result);
    }

    public function testGetViewData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $this->snippetResolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en', false)
            ->willReturn(
                [['content' => ['title' => 'test-1'], 'view' => ['title' => 'test-2', 'template' => 'default']]]
            );

        $result = $this->contentType->getViewData($property->reveal());

        $this->assertEquals([['title' => 'test-2', 'template' => 'default']], $result);
    }

    public function testPreResolve(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');
        $structure->getIsShadow()->wilLReturn(true);
        $structure->getShadowBaseLanguage()->wilLReturn('en');
        $property->getStructure()->willReturn($structure->reveal());
        $property->getValue()->willReturn(['123-123-123']);
        $property->getParams()->willReturn([]);

        $this->contentType->preResolve($property->reveal());

        $this->referenceStore->add('123-123-123')->shouldBeCalled();
    }
}
