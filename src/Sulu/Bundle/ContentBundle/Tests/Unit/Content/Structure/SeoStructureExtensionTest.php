<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Structure;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Content\Structure\SeoStructureExtension;

/**
 * @group unit
 */
class SeoStructureExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var SeoStructureExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->node = $this->prophesize(NodeInterface::class);
        $this->extension = new SeoStructureExtension();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testSave()
    {
        $content = [];
        $this->node->setProperty(Argument::any(), Argument::any())->will(
            function ($arguments) use (&$content) {
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

    public function testSaveWithoutData()
    {
        $content = [];
        $this->node->setProperty(Argument::any(), Argument::any())->will(
            function ($arguments) use (&$content) {
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

    public function testLoad()
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
            function ($arguments) use (&$content) {
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

    public function testLoadWithoutData()
    {
        $content = [];

        $this->node->getPropertyValueWithDefault(Argument::any(), Argument::any())->will(
            function ($arguments) use (&$content) {
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
