<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Meta\MetaTwigExtension;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

class MetaTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<ContentPathInterface>
     */
    private $contentPath;

    /**
     * @var Portal
     */
    private $portal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $webspace = new Webspace();
        $webspace->setKey('sulu_test');

        $locale = new Localization('en');

        $this->portal = new Portal();
        $this->portal->setDefaultLocalization($locale);
        $this->portal->setXDefaultLocalization($locale);

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($this->portal);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($locale);

        $this->contentPath = $this->prophesize(ContentPathInterface::class);

        $this->contentPath->getContentPath('/test', 'sulu_test', 'de')->willReturn('/de/test');
        $this->contentPath->getContentPath('/test-en', 'sulu_test', 'en')->willReturn('/en/test-en');
        $this->contentPath->getContentPath('/test-en-us', 'sulu_test', 'en-us')->willReturn('/en/test-en-us');
        $this->contentPath->getContentPath('/test-en-us', 'sulu_test', 'en_us')->willReturn('/en/test-en-us');
        $this->contentPath->getContentPath('/test-fr', 'sulu_test', 'fr')->willReturn('/fr/test-fr');
    }

    /**
     * Test get alternate links.
     */
    public function testGetAlternateLinks(): void
    {
        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $urls = $extension->getAlternateLinks([
            'de' => '/test',
            'en' => '/test-en',
            'en-us' => '/test-en-us',
            'fr' => '/test-fr',
        ]);

        $this->assertEquals(
            [
                '<link rel="alternate" href="/de/test" hreflang="de" />',
                '<link rel="alternate" href="/en/test-en" hreflang="en" />',
                '<link rel="alternate" href="/en/test-en-us" hreflang="en-us" />',
                '<link rel="alternate" href="/fr/test-fr" hreflang="fr" />',
            ],
            \explode(\PHP_EOL, $urls)
        );
    }

    /**
     * Test get alternate links.
     */
    public function testGetAlternateLinksDifferentDefaultLocale(): void
    {
        $this->portal->setXDefaultLocalization(new Localization('de'));

        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $urls = $extension->getAlternateLinks(
            [
                'de' => '/test',
                'en' => '/test-en',
                'en-us' => '/test-en-us',
                'fr' => '/test-fr',
            ]
        );

        $this->assertEquals(
            [
                '<link rel="alternate" href="/de/test" hreflang="de" />',
                '<link rel="alternate" href="/en/test-en" hreflang="en" />',
                '<link rel="alternate" href="/en/test-en-us" hreflang="en-us" />',
                '<link rel="alternate" href="/fr/test-fr" hreflang="fr" />',
            ],
            \explode(\PHP_EOL, $urls)
        );
    }

    /**
     * Test get alternate links.
     */
    public function testGetAlternateLinksUnderscore(): void
    {
        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $urls = $extension->getAlternateLinks([
            'de' => '/test',
            'en' => '/test-en',
            'en_us' => '/test-en-us',
            'fr' => '/test-fr',
        ]);

        $this->assertEquals(
            [
                '<link rel="alternate" href="/de/test" hreflang="de" />',
                '<link rel="alternate" href="/en/test-en" hreflang="en" />',
                '<link rel="alternate" href="/en/test-en-us" hreflang="en-us" />',
                '<link rel="alternate" href="/fr/test-fr" hreflang="fr" />',
            ],
            \explode(\PHP_EOL, $urls)
        );
    }

    /**
     * test seo meta tags.
     */
    public function testGetSeoMetaTags(): void
    {
        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $metaTags = $extension->getSeoMetaTags(
            [
                'seo' => [
                    'title' => 'SEO Title',
                    'description' => 'SEO Description',
                    'noIndex' => true,
                    'noFollow' => true,
                    'keywords' => 'SEO, Keywords',
                ],
                'excerpt' => [
                    'description' => 'Excerpt Description',
                ],
            ], [
                'title' => 'Page Title',
            ]
        );

        $this->assertEquals(
            [
                '<meta name="description" content="SEO Description">',
                '<meta name="keywords" content="SEO, Keywords">',
                '<meta name="robots" content="NOINDEX, NOFOLLOW">',
            ],
            \explode(\PHP_EOL, $metaTags)
        );
    }

    /**
     * Seo titel.
     */
    public function testGetSeoMetaTagsFallback(): void
    {
        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $metaTags = $extension->getSeoMetaTags(
            [
                'seo' => [
                    'title' => 'SEO Title',
                    'noIndex' => false,
                    'noFollow' => false,
                    'keywords' => 'SEO, Keywords',
                ],
                'excerpt' => [
                    'description' => 'Excerpt Description',
                ],
            ], [
                'title' => 'Page Title',
            ]
        );

        $this->assertEquals(
            [
                '<meta name="description" content="Excerpt Description">',
                '<meta name="keywords" content="SEO, Keywords">',
                '<meta name="robots" content="INDEX, FOLLOW">',
            ],
            \explode(\PHP_EOL, $metaTags)
        );
    }
}
