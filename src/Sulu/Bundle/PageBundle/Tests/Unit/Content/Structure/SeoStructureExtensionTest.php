<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Structure;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Content\Structure\SeoStructureExtension;

class SeoStructureExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var SeoStructureExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->node = $this->prophesize(NodeInterface::class);
        $this->extension = new SeoStructureExtension();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testSave(): void
    {
        $content = [];
        $this->node->setProperty(Argument::any(), Argument::any())->will(
            function($arguments) use (&$content): void {
                $content[$arguments[0]] = $arguments[1];
            }
        );

        $data = [
            'title' => 'Title',
            'description' => 'Description',
            'keywords' => 'Test, Test1',
            'canonicalUrl' => 'http://www.google.at',
            'noIndex' => true,
            'noFollow' => true,
            'hideInSitemap' => true,
        ];
        $this->extension->setLanguageCode('de', 'i18n', null);
        $this->extension->save($this->node->reveal(), $data, 'default', 'de');

        $this->assertEquals(
            [
                'i18n:de-seo-title' => $data['title'],
                'i18n:de-seo-description' => $data['description'],
                'i18n:de-seo-keywords' => $data['keywords'],
                'i18n:de-seo-canonicalUrl' => $data['canonicalUrl'],
                'i18n:de-seo-noIndex' => $data['noIndex'],
                'i18n:de-seo-noFollow' => $data['noFollow'],
                'i18n:de-seo-hideInSitemap' => $data['hideInSitemap'],
            ],
            $content
        );
    }

    public function testSaveWithoutData(): void
    {
        $content = [];
        $this->node->setProperty(Argument::any(), Argument::any())->will(
            function($arguments) use (&$content): void {
                $content[$arguments[0]] = $arguments[1];
            }
        );

        $data = [];
        $this->extension->setLanguageCode('de', 'i18n', null);
        $this->extension->save($this->node->reveal(), $data, 'default', 'de');

        $this->assertEquals(
            [
                'i18n:de-seo-title' => '',
                'i18n:de-seo-description' => '',
                'i18n:de-seo-keywords' => '',
                'i18n:de-seo-canonicalUrl' => '',
                'i18n:de-seo-noIndex' => false,
                'i18n:de-seo-noFollow' => false,
                'i18n:de-seo-hideInSitemap' => false,
            ],
            $content
        );
    }

    public function testLoad(): void
    {
        $data = [
            'title' => 'Title',
            'description' => 'Description',
            'keywords' => 'Test, Test1',
            'canonicalUrl' => 'http://www.google.at',
            'noIndex' => true,
            'noFollow' => true,
            'hideInSitemap' => true,
        ];

        $content = [
            'i18n:de-seo-title' => $data['title'],
            'i18n:de-seo-description' => $data['description'],
            'i18n:de-seo-keywords' => $data['keywords'],
            'i18n:de-seo-canonicalUrl' => $data['canonicalUrl'],
            'i18n:de-seo-noIndex' => $data['noIndex'],
            'i18n:de-seo-noFollow' => $data['noFollow'],
            'i18n:de-seo-hideInSitemap' => $data['hideInSitemap'],
        ];

        $this->node->getPropertyValueWithDefault(Argument::any(), Argument::any())->will(
            function($arguments) use (&$content) {
                if (isset($content[$arguments[0]])) {
                    return $content[$arguments[0]];
                } else {
                    return $arguments[1];
                }
            }
        );

        $this->extension->setLanguageCode('de', 'i18n', null);
        $this->extension->load($this->node->reveal(), 'default', 'de');

        $this->assertEquals(
            [
                'i18n:de-seo-title' => $data['title'],
                'i18n:de-seo-description' => $data['description'],
                'i18n:de-seo-keywords' => $data['keywords'],
                'i18n:de-seo-canonicalUrl' => $data['canonicalUrl'],
                'i18n:de-seo-noIndex' => $data['noIndex'],
                'i18n:de-seo-noFollow' => $data['noFollow'],
                'i18n:de-seo-hideInSitemap' => $data['hideInSitemap'],
            ],
            $content
        );
    }

    public function testLoadWithoutData(): void
    {
        $content = [];

        $this->node->getPropertyValueWithDefault(Argument::any(), Argument::any())->will(
            function($arguments) use (&$content) {
                if (isset($content[$arguments[0]])) {
                    return $content[$arguments[0]];
                } else {
                    return $arguments[1];
                }
            }
        );

        $this->extension->setLanguageCode('de', 'i18n', null);
        $result = $this->extension->load($this->node->reveal(), 'default', 'de');

        $this->assertEquals(
            [
                'title' => '',
                'description' => '',
                'keywords' => '',
                'canonicalUrl' => '',
                'noIndex' => false,
                'noFollow' => false,
                'hideInSitemap' => false,
            ],
            $result
        );
    }
}
