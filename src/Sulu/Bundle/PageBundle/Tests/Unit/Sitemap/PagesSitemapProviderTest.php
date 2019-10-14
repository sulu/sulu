<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Sitemap;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PageBundle\Sitemap\PagesSitemapProvider;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;

class PagesSitemapProviderTest extends TestCase
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
     * @var PagesSitemapProvider
     */
    private $sitemapProvider;

    /**
     * @var PortalInformation
     */
    private $portalInformation;

    /**
     * @var PortalInformation
     */
    private $portalInformationEn;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var string
     */
    private $portalKey = 'sulu_io';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contentRepository = $this->prophesize(ContentRepositoryInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->webspace = $this->prophesize(Webspace::class);
        $this->portalInformation = $this->prophesize(PortalInformation::class);
        $this->portalInformation->getWebspaceKey()->willReturn($this->webspaceKey);
        $this->portalInformation->getPortalKey()->willReturn($this->portalKey);
        $this->portalInformation->getWebspace()->willReturn($this->webspace->reveal());

        $this->portalInformationEn = $this->prophesize(PortalInformation::class);
        $this->portalInformationEn->getWebspaceKey()->willReturn($this->webspaceKey);
        $this->portalInformationEn->getPortalKey()->willReturn($this->portalKey);
        $this->portalInformationEn->getWebspace()->willReturn($this->webspace->reveal());

        $this->sitemapProvider = new PagesSitemapProvider(
            $this->contentRepository->reveal(),
            $this->webspaceManager->reveal(),
            'test'
        );
    }

    public function testBuild()
    {
        $localization = new Localization('de');
        $this->webspace->getDefaultLocalization()->willReturn($localization);
        $this->portalInformation->getLocalization()->willReturn($localization);

        $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains('localhost', 'test')
            ->willReturn([$this->portalInformation->reveal()]);

        /** @var Content[] $pages */
        $pages = [
            $this->createContent('/test-1'),
            $this->createContent('/test-2'),
            $this->createContent('/test-3'),
        ];

        $this->contentRepository->findAllByPortal(
            'de',
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($pages);

        $result = $this->sitemapProvider->build(1, 'http', 'localhost');

        $this->assertCount(3, $result);
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals('http://localhost' . $pages[$i]->getUrl(), $result[$i]->getLoc());
            $this->assertEquals(new \DateTime($pages[$i]->getData()['changed']), $result[$i]->getLastMod());
        }
    }

    public function testBuildMultipleLocales()
    {
        $localization = new Localization('de');
        $localizationEn = new Localization('en');
        $this->webspace->getDefaultLocalization()->willReturn($localization);
        $this->portalInformation->getLocalization()->willReturn($localization);
        $this->portalInformationEn->getLocalization()->willReturn($localizationEn);

        $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains('localhost', 'test')
            ->willReturn([
                $this->portalInformationEn->reveal(),
                $this->portalInformation->reveal(),
            ]);

        $germanPages = [
            $this->createContent('/de-test-1', false, RedirectType::NONE, [
                'de' => '/de-test-1',
                'en' => '/en-test-1',
            ], 'de'),
        ];

        $englishPages = [
            $this->createContent('/en-test-1', false, RedirectType::NONE, [
                'en' => '/en-test-1',
                'de' => '/de-test-1',
            ], 'en'),
        ];

        $this->contentRepository->findAllByPortal(
            'de',
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($germanPages);

        $this->contentRepository->findAllByPortal(
            'en',
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($englishPages);

        $result = $this->sitemapProvider->build(1, 'http', 'localhost');

        $this->assertCount(2, $result);

        $this->assertEquals('http://localhost/en-test-1', $result[0]->getLoc());
        $alternateLinks1 = $result[0]->getAlternateLinks();
        $this->assertCount(2, $alternateLinks1);
        $this->assertEquals('http://localhost/en-test-1', $alternateLinks1['en']->getHref());
        $this->assertEquals('http://localhost/de-test-1', $alternateLinks1['de']->getHref());

        $this->assertEquals('http://localhost/de-test-1', $result[1]->getLoc());
        $alternateLinks2 = $result[1]->getAlternateLinks();
        $this->assertCount(2, $alternateLinks2);
        $this->assertEquals('http://localhost/de-test-1', $alternateLinks2['de']->getHref());
        $this->assertEquals('http://localhost/en-test-1', $alternateLinks2['en']->getHref());
    }

    public function testBuildHideInSitemap()
    {
        $localization = new Localization('de');
        $this->webspace->getDefaultLocalization()->willReturn($localization);
        $this->portalInformation->getLocalization()->willReturn($localization);

        $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains('localhost', 'test')
            ->willReturn([$this->portalInformation->reveal()]);

        /** @var Content[] $pages */
        $pages = [
            $this->createContent('/test-1'),
            $this->createContent('/test-2', true),
            $this->createContent('/test-3', true),
        ];

        $this->contentRepository->findAllByPortal(
            'de',
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($pages);

        $result = $this->sitemapProvider->build(1, 'http', 'localhost');

        $this->assertCount(1, $result);
        $this->assertEquals('http://localhost/test-1', $result[0]->getLoc());
        $this->assertEquals(new \DateTime($pages[0]->getData()['changed']), $result[0]->getLastMod());
    }

    public function testBuildInternalExternalLink()
    {
        $localization = new Localization('de');
        $this->webspace->getDefaultLocalization()->willReturn($localization);
        $this->portalInformation->getLocalization()->willReturn($localization);

        $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains('localhost', 'test')
            ->willReturn([$this->portalInformation->reveal()]);

        /** @var Content[] $pages */
        $pages = [
            $this->createContent('/test-1'),
            $this->createContent('/test-2', false, RedirectType::INTERNAL),
            $this->createContent('/test-3', false, RedirectType::EXTERNAL),
        ];

        $this->contentRepository->findAllByPortal(
            'de',
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($pages);

        $result = $this->sitemapProvider->build(1, 'http', 'localhost');

        $this->assertCount(1, $result);
        $this->assertEquals('http://localhost/test-1', $result[0]->getLoc());
        $this->assertEquals(new \DateTime($pages[0]->getData()['changed']), $result[0]->getLastMod());
    }

    /**
     * Create a new content-page.
     *
     * @param string $url
     * @param bool $hideInSitemap
     * @param int $redirectTarget
     * @param array $urls
     *
     * @return Content
     */
    public function createContent($url, $hideInSitemap = false, $redirectTarget = RedirectType::NONE, $urls = [], $locale = 'de')
    {
        $content = new Content(
            $locale,
            $this->portalKey,
            uniqid('test-'),
            $url,
            WorkflowStage::PUBLISHED,
            $redirectTarget,
            false,
            'default',
            ['seo-hideInSitemap' => $hideInSitemap, 'changed' => (new \DateTime())->format('c')],
            []
        );
        $content->setUrl($url);
        $content->setUrls($urls);

        $this->webspaceManager->findUrlByResourceLocator(
            $content->getUrl(),
            'test',
            $locale,
            $this->webspaceKey,
            'localhost',
            'http'
        )->willReturn('http://localhost' . $content->getUrl());

        return $content;
    }
}
