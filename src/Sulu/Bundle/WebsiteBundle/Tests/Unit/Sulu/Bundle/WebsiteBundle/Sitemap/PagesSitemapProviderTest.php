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
     * @var PagesSitemapProvider
     */
    private $sitemapProvider;

    /**
     * @var string
     */
    private $locale = 'de';

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

        $this->sitemapProvider = new PagesSitemapProvider($this->contentRepository->reveal());
    }

    public function testBuild()
    {
        /** @var Content[] $pages */
        $pages = [
            $this->createContent('/test-1'),
            $this->createContent('/test-2'),
            $this->createContent('/test-3'),
        ];

        $this->contentRepository->findAllByPortal(
            $this->locale,
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($pages);

        $result = $this->sitemapProvider->build(1, $this->portalKey, $this->locale);

        $this->assertCount(3, $result);
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($pages[$i]->getUrl(), $result[$i]->getLoc());
            $this->assertEquals($pages[$i]->getData()['changed'], $result[$i]->getLastMod());
        }
    }

    public function testBuildHideInSitemap()
    {
        /** @var Content[] $pages */
        $pages = [
            $this->createContent('/test-1'),
            $this->createContent('/test-2', true),
            $this->createContent('/test-3', true),
        ];

        $this->contentRepository->findAllByPortal(
            $this->locale,
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($pages);

        $result = $this->sitemapProvider->build(1, $this->portalKey, $this->locale);

        $this->assertCount(1, $result);
        $this->assertEquals($pages[0]->getUrl(), $result[0]->getLoc());
        $this->assertEquals($pages[0]->getData()['changed'], $result[0]->getLastMod());
    }

    public function testBuildInternalExternalLink()
    {
        /** @var Content[] $pages */
        $pages = [
            $this->createContent('/test-1'),
            $this->createContent('/test-2', false, RedirectType::INTERNAL),
            $this->createContent('/test-3', false, RedirectType::EXTERNAL),
        ];

        $this->contentRepository->findAllByPortal(
            $this->locale,
            $this->portalKey,
            MappingBuilder::create()
                ->addProperties(['changed', 'seo-hideInSitemap'])
                ->setResolveUrl(true)
                ->setHydrateGhost(false)
                ->getMapping()
        )->willReturn($pages);

        $result = $this->sitemapProvider->build(1, $this->portalKey, $this->locale);

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
            $this->locale,
            $this->portalKey,
            uniqid('test-'),
            $url,
            WorkflowStage::PUBLISHED,
            $redirectTarget,
            false,
            ['seo-hideInSitemap' => $hideInSitemap, 'changed' => new \DateTime()],
            []
        );
        $content->setUrl($url);
        $content->setUrls($urls);

        return $content;
    }
}
