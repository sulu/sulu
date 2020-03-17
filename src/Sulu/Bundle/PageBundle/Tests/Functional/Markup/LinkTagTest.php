<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Markup;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPool;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\MarkupBundle\Markup\LinkTag;
use Sulu\Bundle\PageBundle\Markup\Link\PageLinkProvider;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\Mapping;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LinkTagTest extends TestCase
{
    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var LinkProviderPoolInterface
     */
    private $linkProviderPool;

    /**
     * @var LinkTag
     */
    private $linkTag;

    protected function setUp(): void
    {
        $this->contentRepository = $this->prophesize(ContentRepositoryInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->request = $this->prophesize(Request::class);
        $this->request->getScheme()->willReturn('http');
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());

        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->environment = 'prod';

        $this->linkProviderPool = new LinkProviderPool(
            [
                'page' => new PageLinkProvider(
                    $this->contentRepository->reveal(),
                    $this->webspaceManager->reveal(),
                    $this->requestStack->reveal(),
                    $this->translator->reveal(),
                    $this->environment
                ),
            ]
        );

        $this->linkTag = new LinkTag($this->linkProviderPool);
    }

    public function provideParseDataDefaultProvider()
    {
        return [
            [
                '<sulu-link href="123-123-123" title="Test-Title">Test-Content</sulu-link>',
                ['href' => '123-123-123', 'title' => 'Test-Title', 'content' => 'Test-Content'],
                '<a href="/de/test" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title"/>',
                ['href' => '123-123-123', 'title' => 'Test-Title'],
                '<a href="/de/test" title="Test-Title">Pagetitle</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title"></sulu-link>',
                ['href' => '123-123-123', 'title' => 'Test-Title'],
                '<a href="/de/test" title="Test-Title">Pagetitle</a>',
            ],
            [
                '<sulu-link href="123-123-123">Test-Content</sulu-link>',
                ['href' => '123-123-123', 'content' => 'Test-Content'],
                '<a href="/de/test" title="Pagetitle">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" target="_blank">Test-Content</sulu-link>',
                ['href' => '123-123-123', 'title' => 'Test-Title', 'target' => '_blank', 'content' => 'Test-Content'],
                '<a href="/de/test" title="Test-Title" target="_blank">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" target="_self">Test-Content</sulu-link>',
                ['href' => '123-123-123', 'title' => 'Test-Title', 'target' => '_self', 'content' => 'Test-Content'],
                '<a href="/de/test" title="Test-Title" target="_self">Test-Content</a>',
            ],
        ];
    }

    /**
     * @dataProvider provideParseDataDefaultProvider
     */
    public function testParseAllDefaultProvider($tag, $attributes, $expected)
    {
        $content = $this->createContent('123-123-123', 'Pagetitle', '/test');
        $this->contentRepository->findByUuids(['123-123-123'], 'de', Argument::type(Mapping::class))
            ->willReturn([$content]);

        $this->webspaceManager->findUrlByResourceLocator(
            $content->getUrl(),
            $this->environment,
            $content->getLocale(),
            $content->getWebspaceKey(),
            null,
            'http'
        )->willReturn('/de' . $content->getUrl());

        $result = $this->linkTag->parseAll([$tag => $attributes], 'de');

        $this->assertEquals([$tag => $expected], $result);
    }

    public function testParseAllMultipleTagsDefaultProvider()
    {
        $content1 = $this->createContent('123-123-123', '1', '/test-1');
        $content2 = $this->createContent('312-312-312', '2', '/test-2');
        $this->contentRepository->findByUuids(['123-123-123', '312-312-312'], 'de', Argument::type(Mapping::class))
            ->willReturn([$content1, $content2])->shouldBeCalledTimes(1);

        $this->webspaceManager->findUrlByResourceLocator(
            $content1->getUrl(),
            $this->environment,
            $content1->getLocale(),
            $content1->getWebspaceKey(),
            null,
            'http'
        )->willReturn('/de' . $content1->getUrl());

        $this->webspaceManager->findUrlByResourceLocator(
            $content2->getUrl(),
            $this->environment,
            $content2->getLocale(),
            $content2->getWebspaceKey(),
            null,
            'http'
        )->willReturn('/de' . $content2->getUrl());

        $tag1 = '<sulu-link href="123-123-123">Test-Content</sulu-link>';
        $tag2 = '<sulu-link href="123-123-123" title="Test-Title"/>';
        $tag3 = '<sulu-link href="123-123-123" title="Test-Title">Test-Content</sulu-link>';
        $tag4 = '<sulu-link href="123-123-123" title="Test-Title" target="_blank">Test-Content</sulu-link>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'content' => 'Test-Content'],
                $tag2 => ['href' => '312-312-312', 'title' => 'Test-Title'],
                $tag3 => ['href' => '123-123-123', 'title' => 'Test-Title', 'content' => 'Test-Content'],
                $tag4 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="/de/test-1" title="1">Test-Content</a>',
                $tag2 => '<a href="/de/test-2" title="Test-Title">2</a>',
                $tag3 => '<a href="/de/test-1" title="Test-Title">Test-Content</a>',
                $tag4 => '<a href="/de/test-1" title="Test-Title" target="_blank">Test-Content</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleTagsMissingContentDefaultProvider()
    {
        $this->contentRepository->findByUuids(['123-123-123'], 'de', Argument::type(Mapping::class))
            ->willReturn([])->shouldBeCalledTimes(1);

        $tag1 = '<sulu-link href="123-123-123">Test-Content</sulu-link>';
        $tag2 = '<sulu-link href="123-123-123" title="Test-Title"/>';
        $tag3 = '<sulu-link href="123-123-123" title="Test-Title">Test-Content</sulu-link>';
        $tag4 = '<sulu-link href="123-123-123"/>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'content' => 'Test-Content'],
                $tag2 => ['href' => '123-123-123', 'title' => 'Test-Title'],
                $tag3 => ['href' => '123-123-123', 'title' => 'Test-Title', 'content' => 'Test-Content'],
                $tag4 => ['href' => '123-123-123'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => 'Test-Content',
                $tag2 => 'Test-Title',
                $tag3 => 'Test-Content',
                $tag4 => '',
            ],
            $result
        );
    }

    public function testValidateDefaultProvider()
    {
        $content = $this->createContent('123-123-123', 'Pagetitle', '/test', 'published-date');
        $this->contentRepository->findByUuids(['123-123-123'], 'de', Argument::type(Mapping::class))
            ->willReturn([$content]);

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="Test-Title">Test-Content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }

    public function testValidateInvalidDefaultProvider()
    {
        $this->contentRepository->findByUuids(['123-123-123'], 'de', Argument::type(Mapping::class))
            ->willReturn([]);

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="Test-Title">Test-Content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            ['<sulu-link href="123-123-123" title="Test-Title">Test-Content</sulu-link>' => LinkTag::VALIDATE_REMOVED],
            $result
        );
    }

    public function testValidateMixedDefaultProvider()
    {
        $content = $this->createContent('123-123-123', 'Pagetitle', '/test', 'published-date');
        $this->contentRepository->findByUuids(['123-123-123', '312-312-312'], 'de', Argument::type(Mapping::class))
            ->willReturn([$content]);

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="Test-Title">Test-Content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
                '<sulu-link href="312-312-312" title="Test-Title">Test-Content</sulu-link>' => [
                    'href' => '312-312-312',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                '<sulu-link href="312-312-312" title="Test-Title">Test-Content</sulu-link>' => LinkTag::VALIDATE_REMOVED,
            ],
            $result
        );
    }

    private function createContent($id, $title, $url, $published = '', $webspaceKey = 'sulu_io', $locale = 'de')
    {
        $content = new Content(
            $locale,
            $webspaceKey,
            $id,
            $url,
            WorkflowStage::PUBLISHED,
            1,
            false,
            'simple',
            [
                'title' => $title,
                'published' => $published,
            ],
            []
        );
        $content->setUrl($url);

        return $content;
    }
}
