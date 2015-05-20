<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Structure;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Content\Structure\SeoStructureExtension;

abstract class TestProperty implements \Iterator, NodeInterface
{
}

/**
 * @group unit
 */
class SeoStructureExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeInterface
     */
    private $nodeMock;

    /**
     * @var SeoStructureExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->nodeMock = $this->getMock('Sulu\Bundle\ContentBundle\Tests\Unit\Structure\TestProperty');
        $this->extension = new SeoStructureExtension();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testSave()
    {
        $content = array();
        $this->nodeMock
            ->expects($this->exactly(7))
            ->method('setProperty')
            ->will(
                $this->returnCallback(
                    function ($property, $value) use (&$content) {
                        $content[$property] = $value;
                    }
                )
            );

        $data = array(
            'title' => 'Title',
            'description' => 'Description',
            'keywords' => 'Test, Test1',
            'canonicalUrl' => 'http://www.google.at',
            'noIndex' => true,
            'noFollow' => true,
            'hideInSitemap' => true,
        );
        $this->extension->setLanguageCode('de', 'i18n', null);
        $this->extension->save($this->nodeMock, $data, 'default', 'de');

        $this->assertEquals(
            array(
                'i18n:de-seo-title' => $data['title'],
                'i18n:de-seo-description' => $data['description'],
                'i18n:de-seo-keywords' => $data['keywords'],
                'i18n:de-seo-canonicalUrl' => $data['canonicalUrl'],
                'i18n:de-seo-noIndex' => $data['noIndex'],
                'i18n:de-seo-noFollow' => $data['noFollow'],
                'i18n:de-seo-hideInSitemap' => $data['hideInSitemap'],
            ),
            $content
        );
    }

    public function testSaveWithoutData()
    {
        $content = array();
        $this->nodeMock
            ->expects($this->exactly(7))
            ->method('setProperty')
            ->will(
                $this->returnCallback(
                    function ($property, $value) use (&$content) {
                        $content[$property] = $value;
                    }
                )
            );

        $data = array();
        $this->extension->setLanguageCode('de', 'i18n', null);
        $this->extension->save($this->nodeMock, $data, 'default', 'de');

        $this->assertEquals(
            array(
                'i18n:de-seo-title' => '',
                'i18n:de-seo-description' => '',
                'i18n:de-seo-keywords' => '',
                'i18n:de-seo-canonicalUrl' => '',
                'i18n:de-seo-noIndex' => false,
                'i18n:de-seo-noFollow' => false,
                'i18n:de-seo-hideInSitemap' => false,
            ),
            $content
        );
    }

    public function testLoad()
    {
        $data = array(
            'title' => 'Title',
            'description' => 'Description',
            'keywords' => 'Test, Test1',
            'canonicalUrl' => 'http://www.google.at',
            'noIndex' => true,
            'noFollow' => true,
            'hideInSitemap' => true,
        );

        $content = array(
            'i18n:de-seo-title' => $data['title'],
            'i18n:de-seo-description' => $data['description'],
            'i18n:de-seo-keywords' => $data['keywords'],
            'i18n:de-seo-canonicalUrl' => $data['canonicalUrl'],
            'i18n:de-seo-noIndex' => $data['noIndex'],
            'i18n:de-seo-noFollow' => $data['noFollow'],
            'i18n:de-seo-hideInSitemap' => $data['hideInSitemap'],
        );
        $this->nodeMock
            ->expects($this->exactly(7))
            ->method('getPropertyValueWithDefault')
            ->will(
                $this->returnCallback(
                    function ($property, $default) use (&$content) {
                        if (isset($content[$property])) {
                            return $content[$property];
                        } else {
                            return $default;
                        }
                    }
                )
            );

        $this->extension->setLanguageCode('de', 'i18n', null);
        $this->extension->load($this->nodeMock, 'default', 'de');

        $this->assertEquals(
            array(
                'i18n:de-seo-title' => $data['title'],
                'i18n:de-seo-description' => $data['description'],
                'i18n:de-seo-keywords' => $data['keywords'],
                'i18n:de-seo-canonicalUrl' => $data['canonicalUrl'],
                'i18n:de-seo-noIndex' => $data['noIndex'],
                'i18n:de-seo-noFollow' => $data['noFollow'],
                'i18n:de-seo-hideInSitemap' => $data['hideInSitemap'],
            ),
            $content
        );
    }

    public function testLoadWithoutData()
    {
        $content = array();
        $this->nodeMock
            ->expects($this->exactly(7))
            ->method('getPropertyValueWithDefault')
            ->will(
                $this->returnCallback(
                    function ($property, $default) use (&$content) {
                        if (isset($content[$property])) {
                            return $content[$property];
                        } else {
                            return $default;
                        }
                    }
                )
            );

        $this->extension->setLanguageCode('de', 'i18n', null);
        $result = $this->extension->load($this->nodeMock, 'default', 'de');

        $this->assertEquals(
            array(
                'title' => '',
                'description' => '',
                'keywords' => '',
                'canonicalUrl' => '',
                'noIndex' => false,
                'noFollow' => false,
                'hideInSitemap' => false,
            ),
            $result
        );
    }
}
