<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Unit\Infrastructure\Symfony\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Exception\SnippetAreaNotFoundException;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetArea;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Sulu\Snippet\Infrastructure\Symfony\Twig\SnippetAreaTwigExtension;

class SnippetAreaTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    private SnippetAreaTwigExtension $extension;

    /** @var ObjectProphecy<SnippetAreaRepositoryInterface> */
    private ObjectProphecy $snippetAreaRepository;

    /** @var ObjectProphecy<ContentAggregatorInterface> */
    private ObjectProphecy $contentAggregator;

    /** @var ObjectProphecy<RequestAnalyzerInterface> */
    private ObjectProphecy $requestAnalyzer;

    private ReferenceStoreInterface $referenceStore;

    protected function setUp(): void
    {
        $this->snippetAreaRepository = $this->prophesize(SnippetAreaRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->referenceStore = new ReferenceStore();

        $this->extension = new SnippetAreaTwigExtension(
            $this->snippetAreaRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->referenceStore,
        );
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('sulu_snippet_load_by_area', $functions[0]->getName());
    }

    public function testLoadSnippetByAreaWithExplicitParameters(): void
    {
        $areaKey = 'header';
        $webspaceKey = 'example';
        $locale = 'en';

        // Create real objects
        $snippet = new Snippet('test-snippet-uuid');

        $snippetDimensionContent = new SnippetDimensionContent($snippet);
        $snippetDimensionContent->setTemplateData(['title' => 'Test Snippet']);

        $snippetArea = new SnippetArea($areaKey, $webspaceKey);
        $snippetArea->setSnippet($snippet);

        $this->snippetAreaRepository->findOneBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ])->willReturn($snippetArea);

        $this->contentAggregator->aggregate(
            $snippet,
            [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ]
        )->willReturn($snippetDimensionContent);

        $result = $this->extension->loadSnippetByArea($areaKey, $webspaceKey, $locale);

        $this->assertSame($snippetDimensionContent, $result);
        $this->assertSame('Test Snippet', $result->getTitle());

        $this->assertSame(
            [SnippetInterface::RESOURCE_KEY . '-test-snippet-uuid' => SnippetInterface::RESOURCE_KEY . '-test-snippet-uuid'],
            $this->referenceStore->getAll()
        );
    }

    public function testLoadSnippetByAreaWithAutoDetectedParameters(): void
    {
        $areaKey = 'footer';
        $webspaceKey = 'example';
        $locale = 'de';

        $webspace = new Webspace();
        $webspace->setKey($webspaceKey);

        $localization = new Localization();
        $localization->setLanguage('de');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $snippet = new Snippet('footer-snippet-uuid');
        $snippetDimensionContent = new SnippetDimensionContent($snippet);
        $snippetDimensionContent->setTemplateData(['title' => 'Footer Snippet']);

        $snippetArea = new SnippetArea($areaKey, $webspaceKey);
        $snippetArea->setSnippet($snippet);

        $this->snippetAreaRepository->findOneBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ])->willReturn($snippetArea);

        $this->contentAggregator->aggregate(
            $snippet,
            [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ]
        )->willReturn($snippetDimensionContent);

        $result = $this->extension->loadSnippetByArea($areaKey);

        $this->assertSame($snippetDimensionContent, $result);
        $this->assertSame('Footer Snippet', $result->getTitle());

        $this->assertSame(
            [SnippetInterface::RESOURCE_KEY . '-footer-snippet-uuid' => SnippetInterface::RESOURCE_KEY . '-footer-snippet-uuid'],
            $this->referenceStore->getAll()
        );
    }

    public function testLoadSnippetByAreaWithNoWebspace(): void
    {
        $areaKey = 'header';

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $result = $this->extension->loadSnippetByArea($areaKey);

        $this->assertNull($result);
    }

    public function testLoadSnippetByAreaWithNoLocalization(): void
    {
        $areaKey = 'header';
        $webspaceKey = 'example';

        $webspace = new Webspace();
        $webspace->setKey($webspaceKey);

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);

        $result = $this->extension->loadSnippetByArea($areaKey);

        $this->assertNull($result);
    }

    public function testLoadSnippetByAreaWithNoSnippetArea(): void
    {
        $areaKey = 'header';
        $webspaceKey = 'example';
        $locale = 'en';

        $this->snippetAreaRepository->findOneBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ])->willReturn(null);

        $result = $this->extension->loadSnippetByArea($areaKey, $webspaceKey, $locale);

        $this->assertNull($result);
    }

    public function testLoadSnippetByAreaWithSnippetAreaButNoSnippet(): void
    {
        $areaKey = 'header';
        $webspaceKey = 'example';
        $locale = 'en';

        $snippetArea = new SnippetArea($areaKey, $webspaceKey);

        $this->snippetAreaRepository->findOneBy([
            'webspaceKey' => $webspaceKey,
            'areaKey' => $areaKey,
        ])->willReturn($snippetArea);

        $result = $this->extension->loadSnippetByArea($areaKey, $webspaceKey, $locale);

        $this->assertNull($result);
    }

    public function testLoadSnippetByAreaWithNotFoundException(): void
    {
        $areaKey = 'header';
        $webspaceKey = 'example';
        $locale = 'en';

        $this->snippetAreaRepository->findOneBy(Argument::any())
            ->willThrow(new SnippetAreaNotFoundException(['areaKey' => $areaKey]));

        $this->expectException(SnippetAreaNotFoundException::class);
        $this->extension->loadSnippetByArea($areaKey, $webspaceKey, $locale);
    }
}
