<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Seo;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;

class SeoTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SeoTwigExtension
     */
    private $seoTwigExtension;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ContentPathInterface
     */
    private $contentPath;

    public function setUp()
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->contentPath = $this->prophesize(ContentPathInterface::class);
        $this->seoTwigExtension = new SeoTwigExtension($this->requestAnalyzer->reveal(), $this->contentPath->reveal());
    }

    public function testGetFunctions()
    {
        $result = $this->seoTwigExtension->getFunctions();

        $this->assertEquals(
            new \Twig_SimpleFunction('sulu_seo', [$this->seoTwigExtension, 'renderSeoTags']),
            $result[0]
        );
    }

    /**
     * @dataProvider provideSeoData
     *
     * @param $seoExtension
     * @param $content
     * @param $urls
     * @param $defaultLocale
     * @param $shadowBaseLocale
     * @param $expectedResults
     * @param array $unexpectedResults
     */
    public function testRenderSeoTags(
        $seoExtension,
        $content,
        $urls,
        $defaultLocale,
        $shadowBaseLocale,
        $expectedResults,
        $unexpectedResults = []
    ) {
        /** @var Localization $localization */
        $localization = $this->prophesize(Localization::class);
        $localization->getLocalization()->willReturn($defaultLocale);

        /** @var Portal $portal */
        $portal = $this->prophesize(Portal::class);
        $portal->getDefaultLocalization()->willReturn($localization->reveal());

        $this->requestAnalyzer->getPortal()->willReturn($portal->reveal());

        $webspace = $this->prophesize(Webspace::class);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->contentPath->getContentPath(Argument::cetera())->will(
            function ($arguments) {
                return '/' . str_replace('_', '-', $arguments[2]) . $arguments[0];
            }
        );

        $result = $this->seoTwigExtension->renderSeoTags($seoExtension, $content, $urls, $shadowBaseLocale);

        foreach ($expectedResults as $expectedResult) {
            $this->assertContains($expectedResult, $result);
        }

        foreach ($unexpectedResults as $unexpectedResult) {
            $this->assertNotContains($unexpectedResult, $result);
        }
    }

    public function testRenderSeoTagsWithoutPortal()
    {
        $this->seoTwigExtension->renderSeoTags([], [], [], null);
    }

    public function provideSeoData()
    {
        return [
            [
                [
                    'title' => 'SEO title',
                    'description' => 'SEO description',
                    'keywords' => 'SEO keywords',
                    'canonicalUrl' => '/canonical-url',
                    'noIndex' => true,
                    'noFollow' => true,
                    'hideInSitemap' => true,
                ],
                [
                    'title' => 'Content title',
                ],
                [
                    'en' => '/url-en',
                    'de' => '/url-de',
                ],
                'en',
                'en',
                [
                    '<title>SEO title</title>',
                    '<meta name="description" content="SEO description"/>',
                    '<meta name="keywords" content="SEO keywords"/>',
                    '<meta name="robots" content="noIndex,noFollow"/>',
                    '<link rel="alternate" href="/en/url-en" hreflang="x-default"/>',
                    '<link rel="alternate" href="/en/url-en" hreflang="en"/>',
                    '<link rel="alternate" href="/de/url-de" hreflang="de"/>',
                    '<link rel="canonical" href="/canonical-url"/>',
                ],
            ],
            [
                [
                    'title' => '',
                    'description' => '',
                    'keywords' => '',
                    'canonicalUrl' => '',
                    'noIndex' => false,
                    'noFollow' => false,
                    'hideInSitemap' => true,
                ],
                [
                    'title' => 'Content title',
                ],
                [
                    'en' => '/url-en',
                    'de' => '/url-de',
                ],
                'de',
                'en',
                [
                    '<title>Content title</title>',
                    '<meta name="robots" content="index,follow"/>',
                    '<link rel="alternate" href="/de/url-de" hreflang="x-default"/>',
                    '<link rel="alternate" href="/en/url-en" hreflang="en"/>',
                    '<link rel="alternate" href="/de/url-de" hreflang="de"/>',
                    '<link rel="canonical" href="/en/url-en"/>',
                ],
                [
                    '<meta name="description" content=""/>',
                    '<meta name="keywords" content=""/>',
                ],
            ],
            [
                [
                    'title' => '',
                    'description' => '',
                    'keywords' => '',
                    'canonicalUrl' => '',
                    'noIndex' => false,
                    'noFollow' => false,
                    'hideInSitemap' => true,
                ],
                [
                    'title' => 'Content title',
                ],
                [
                    'en' => '/url-en',
                    'de' => '/url-de',
                ],
                'de',
                null,
                [
                    '<title>Content title</title>',
                    '<meta name="robots" content="index,follow"/>',
                    '<link rel="alternate" href="/de/url-de" hreflang="x-default"/>',
                    '<link rel="alternate" href="/en/url-en" hreflang="en"/>',
                    '<link rel="alternate" href="/de/url-de" hreflang="de"/>',
                ],
                [
                    '<meta name="description" content=""/>',
                    '<meta name="keywords" content=""/>',
                    '<link rel="canonical" href=""/>',
                ],
            ],
            [
                [
                    'title' => '',
                    'description' => '',
                    'keywords' => '',
                    'canonicalUrl' => '/canonical-url',
                    'noIndex' => false,
                    'noFollow' => false,
                    'hideInSitemap' => true,
                ],
                [
                    'title' => 'Content title',
                ],
                [
                    'en' => '/url-en',
                    'de' => '/url-de',
                    'de_at' => '/url-de-at',
                ],
                'de',
                null,
                [
                    '<title>Content title</title>',
                    '<meta name="robots" content="index,follow"/>',
                    '<link rel="alternate" href="/de/url-de" hreflang="x-default"/>',
                    '<link rel="alternate" href="/en/url-en" hreflang="en"/>',
                    '<link rel="alternate" href="/de/url-de" hreflang="de"/>',
                    '<link rel="alternate" href="/de-at/url-de-at" hreflang="de-at"/>',
                    '<link rel="canonical" href="/canonical-url"/>',
                ],
                [
                    '<meta name="description" content=""/>',
                    '<meta name="keywords" content=""/>',
                ],
            ],
        ];
    }
}
