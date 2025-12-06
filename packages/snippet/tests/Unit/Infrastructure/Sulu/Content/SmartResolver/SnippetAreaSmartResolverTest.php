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

namespace Sulu\Snippet\Tests\Unit\Infrastructure\Sulu\Content\SmartResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\SmartResolver\SnippetAreaSmartResolver;

#[CoversClass(SnippetAreaSmartResolver::class)]
class SnippetAreaSmartResolverTest extends TestCase
{
    use ProphecyTrait;

    private SnippetAreaSmartResolver $resolver;

    /** @var ObjectProphecy<SnippetAreaRepositoryInterface> */
    private ObjectProphecy $snippetAreaRepository;

    /** @var ObjectProphecy<RequestAnalyzerInterface> */
    private ObjectProphecy $requestAnalyzer;

    protected function setUp(): void
    {
        $this->snippetAreaRepository = $this->prophesize(SnippetAreaRepositoryInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->resolver = new SnippetAreaSmartResolver(
            $this->snippetAreaRepository->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    private function createWebspace(string $key): Webspace
    {
        $webspace = new Webspace();
        $webspace->setKey($key);

        return $webspace;
    }

    public function testGetType(): void
    {
        $this->assertSame('snippet_area_default', SnippetAreaSmartResolver::getType());
    }

    public function testResolveWithSnippet(): void
    {
        $smartResolvable = new SmartResolvable(
            data: ['areaKey' => 'header'],
            resourceLoaderKey: 'snippet_area_default',
            priority: 2048
        );

        $webspace = $this->createWebspace('sulu_io');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => 'sulu_io',
            'areaKey' => 'header',
        ])->willReturn('snippet-uuid-123');

        $result = $this->resolver->resolve($smartResolvable, 'en');

        $content = $result->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame('snippet-uuid-123', $content->getId());
        $this->assertSame('snippet', $content->getResourceLoaderKey());
        $this->assertSame('snippets', $content->getResourceKey());
        $this->assertSame(['id' => 'snippet-uuid-123'], $result->getView());
    }

    public function testResolveWithoutSnippet(): void
    {
        $smartResolvable = new SmartResolvable(
            data: ['areaKey' => 'footer'],
            resourceLoaderKey: 'snippet_area_default',
            priority: 2048
        );

        $webspace = $this->createWebspace('sulu_io');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => 'sulu_io',
            'areaKey' => 'footer',
        ])->willReturn(null);

        $result = $this->resolver->resolve($smartResolvable, 'en');

        $this->assertNull($result->getContent());
        $this->assertSame([], $result->getView());
    }

    public function testResolveWithoutWebspace(): void
    {
        $smartResolvable = new SmartResolvable(
            data: ['areaKey' => 'sidebar'],
            resourceLoaderKey: 'snippet_area_default',
            priority: 2048
        );

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $result = $this->resolver->resolve($smartResolvable, 'en');

        $this->assertNull($result->getContent());
        $this->assertSame([], $result->getView());

        $this->snippetAreaRepository->findOneUuidBy()->shouldNotHaveBeenCalled();
    }

    public function testResolveWithArrayAndSingleSnippet(): void
    {
        $smartResolvable = new SmartResolvable(
            data: ['areaKey' => ['header']],
            resourceLoaderKey: 'snippet_area_default',
            priority: 2048
        );

        $webspace = $this->createWebspace('sulu_io');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => 'sulu_io',
            'areaKey' => 'header',
        ])->willReturn('snippet-uuid-123');

        $result = $this->resolver->resolve($smartResolvable, 'en');

        $content = $result->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);
        $this->assertSame('snippet-uuid-123', $content[0]->getId());
        $this->assertSame('snippet', $content[0]->getResourceLoaderKey());
        $this->assertSame('snippets', $content[0]->getResourceKey());
        $this->assertSame(['ids' => ['snippet-uuid-123']], $result->getView());
    }

    public function testResolveWithArrayAndMultipleSnippets(): void
    {
        $smartResolvable = new SmartResolvable(
            data: ['areaKey' => ['header', 'footer']],
            resourceLoaderKey: 'snippet_area_default',
            priority: 2048
        );

        $webspace = $this->createWebspace('sulu_io');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => 'sulu_io',
            'areaKey' => 'header',
        ])->willReturn('snippet-uuid-header');

        $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => 'sulu_io',
            'areaKey' => 'footer',
        ])->willReturn('snippet-uuid-footer');

        $result = $this->resolver->resolve($smartResolvable, 'en');

        $content = $result->getContent();
        $this->assertIsArray($content);
        $this->assertCount(2, $content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);
        $this->assertSame('snippet-uuid-header', $content[0]->getId());
        $this->assertInstanceOf(ResolvableResource::class, $content[1]);
        $this->assertSame('snippet-uuid-footer', $content[1]->getId());
        $this->assertSame(['ids' => ['snippet-uuid-header', 'snippet-uuid-footer']], $result->getView());
    }

    public function testResolveWithArrayAndNoSnippet(): void
    {
        $smartResolvable = new SmartResolvable(
            data: ['areaKey' => ['footer']],
            resourceLoaderKey: 'snippet_area_default',
            priority: 2048
        );

        $webspace = $this->createWebspace('sulu_io');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->snippetAreaRepository->findOneUuidBy([
            'webspaceKey' => 'sulu_io',
            'areaKey' => 'footer',
        ])->willReturn(null);

        $result = $this->resolver->resolve($smartResolvable, 'en');

        $this->assertSame([], $result->getContent());
        $this->assertSame(['ids' => []], $result->getView());
    }

    public function testResolveWithArrayAndNoWebspace(): void
    {
        $smartResolvable = new SmartResolvable(
            data: ['areaKey' => ['sidebar']],
            resourceLoaderKey: 'snippet_area_default',
            priority: 2048
        );

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $result = $this->resolver->resolve($smartResolvable, 'en');

        $this->assertSame([], $result->getContent());
        $this->assertSame(['ids' => []], $result->getView());

        $this->snippetAreaRepository->findOneUuidBy()->shouldNotHaveBeenCalled();
    }
}
