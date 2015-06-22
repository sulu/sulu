<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Meta\MetaTwigExtension;
use Sulu\Component\Content\Structure;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;


class MetaTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ContentPathInterface
     */
    private $contentPath;

    protected function setUp()
    {
        parent::setUp();

        $this->requestAnalyzer = $this->prophesize('Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface');

        $webspace = new Webspace();
        $webspace->setKey('sulu_test');


        $locale = new Localization();
        $locale->setLanguage('en');

        $portal = new Portal();
        $portal->setDefaultLocalization($locale);

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($locale);

        $this->contentPath = $this->prophesize('Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface');

        $this->contentPath->getContentPath('/test', 'sulu_test', 'de')->willReturn('/de/test');
        $this->contentPath->getContentPath('/test-en', 'sulu_test', 'en')->willReturn('/en/test-en');
        $this->contentPath->getContentPath('/test-en-us', 'sulu_test', 'en-us')->willReturn('/en/test-en-us');
        $this->contentPath->getContentPath('/test-fr', 'sulu_test', 'fr')->willReturn('/fr/test-fr');
    }

    /**
     * Test get alternate links
     */
    public function testGetAlternateLinks()
    {
        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $urls = $extension->getAlternateLinks(array(
            'de' => '/test',
            'en' => '/test-en',
            'en-us' => '/test-en-us',
            'fr' => '/test-fr',
        ));

        $this->assertEquals(
            array(
                '<link rel="alternate" href="/de/test" hreflang="de" />',
                '<link rel="alternate" href="/en/test-en" hreflang="x-default" />',
                '<link rel="alternate" href="/en/test-en" hreflang="en" />',
                '<link rel="alternate" href="/en/test-en-us" hreflang="en-us" />',
                '<link rel="alternate" href="/fr/test-fr" hreflang="fr" />',
            ), explode(PHP_EOL, $urls)
        );
    }

    /**
     * test seo meta tags
     */
    public function testGetSeoMetaTags()
    {
        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $metaTags = $extension->getSeoMetaTags(
            array(
                'seo' => array(
                    'title' => 'SEO Title',
                    'description' => 'SEO Description',
                    'noIndex' => true,
                    'noFollow' => true,
                    'keywords' => 'SEO, Keywords',
                ),
                'excerpt' => array(
                    'description' => 'Excerpt Description'
                ),
            ), array(
                'title' => 'Page Title'
            )
        );

        $this->assertEquals(
            array(
                '<meta name="description" content="SEO Description">',
                '<meta name="keywords" content="SEO, Keywords">',
                '<meta name="robots" content="NOINDEX, NOFOLLOW">',
            ), explode(PHP_EOL, $metaTags)
        );
    }

    /**
     * Seo titel
     */
    public function testGetSeoMetaTagsFallback()
    {
        $extension = new MetaTwigExtension(
            $this->requestAnalyzer->reveal(),
            $this->contentPath->reveal()
        );

        $metaTags = $extension->getSeoMetaTags(
            array(
                'seo' => array(
                    'title' => 'SEO Title',
                    'noIndex' => false,
                    'noFollow' => false,
                    'keywords' => 'SEO, Keywords',
                ),
                'excerpt' => array(
                    'description' => 'Excerpt Description'
                ),
            ), array(
                'title' => 'Page Title'
            )
        );

        $this->assertEquals(
            array(
                '<meta name="description" content="Excerpt Description">',
                '<meta name="keywords" content="SEO, Keywords">',
                '<meta name="robots" content="INDEX, FOLLOW">',
            ), explode(PHP_EOL, $metaTags)
        );
    }
}
