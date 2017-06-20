<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Sitemap;

use Sulu\Bundle\WebsiteBundle\Sitemap\Provider\PagesSitemapProvider;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;

/**
 * Tests for PagesSitemapProvider.
 */
class PagesSitemapProviderTest extends \PHPUnit_Framework_TestCase
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
     * @var string
     */
    private $portalKey = 'sulu_io';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contentRepository = $this->prophesize(ContentRepositoryInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->sitemapProvider = new PagesSitemapProvider(
            $this->contentRepository->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    public function testBuild()
    {
        $portal = new Portal();
        $portal->addLocalization(new Localization('de'));
        $this->webspaceManager->findPortalByKey($this->portalKey)->willReturn($portal);

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

        $result = $this->sitemapProvider->build(1, $this->portalKey);

        $this->assertCount(3, $result);
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($pages[$i]->getUrl(), $result[$i]->getLoc());
            $this->assertEquals($pages[$i]->getData()['changed'], $result[$i]->getLastMod());
        }
    }

    public function testBuildMultipleLocales()
    {
        $portal = new Portal();
        $portal->addLocalization(new Localization('de'));
        $portal->addLocalization(new Localization('en'));
        $this->webspaceManager->findPortalByKey($this->portalKey)->willReturn($portal);

        $germanPages = [
            $this->createContent('/de-test-1', false, RedirectType::NONE, [
                'de' => '/de-test-1',
                'en' => '/en-test-1',
            ]),
        ];

        $englishPages = [
            $this->createContent('/en-test-1', false, RedirectType::NONE, [
                'en' => '/en-test-1',
                'de' => '/de-test-1',
            ]),
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

        $result = $this->sitemapProvider->build(1, $this->portalKey);

        $this->assertCount(2, $result);

        $this->assertEquals('/en-test-1', $result[0]->getLoc());
        $alternateLinks1 = $result[0]->getAlternateLinks();
        $this->assertCount(2, $alternateLinks1);
        $this->assertEquals('/en-test-1', $alternateLinks1['en']->getHref());
        $this->assertEquals('/de-test-1', $alternateLinks1['de']->getHref());

        $this->assertEquals('/de-test-1', $result[1]->getLoc());
        $alternateLinks2 = $result[1]->getAlternateLinks();
        $this->assertCount(2, $alternateLinks2);
        $this->assertEquals('/de-test-1', $alternateLinks2['de']->getHref());
        $this->assertEquals('/en-test-1', $alternateLinks2['en']->getHref());
    }

    public function testBuildHideInSitemap()
    {
        $portal = new Portal();
        $portal->addLocalization(new Localization('de'));
        $this->webspaceManager->findPortalByKey($this->portalKey)->willReturn($portal);

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

        $result = $this->sitemapProvider->build(1, $this->portalKey);

        $this->assertCount(1, $result);
        $this->assertEquals($pages[0]->getUrl(), $result[0]->getLoc());
        $this->assertEquals($pages[0]->getData()['changed'], $result[0]->getLastMod());
    }

    public function testBuildInternalExternalLink()
    {
        $portal = new Portal();
        $portal->addLocalization(new Localization('de'));
        $this->webspaceManager->findPortalByKey($this->portalKey)->willReturn($portal);

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

        $result = $this->sitemapProvider->build(1, $this->portalKey);

        $this->assertCount(1, $result);
        $this->assertEquals($pages[0]->getUrl(), $result[0]->getLoc());
        $this->assertEquals($pages[0]->getData()['changed'], $result[0]->getLastMod());
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
    public function createContent($url, $hideInSitemap = false, $redirectTarget = RedirectType::NONE, $urls = [])
    {
        $content = new Content(
            'de',
            $this->portalKey,
            uniqid('test-'),
            $url,
            WorkflowStage::PUBLISHED,
            $redirectTarget,
            false,
            'default',
            ['seo-hideInSitemap' => $hideInSitemap, 'changed' => new \DateTime()],
            []
        );
        $content->setUrl($url);
        $content->setUrls($urls);

        return $content;
    }
}
