<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Structure\Loader;

use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Component\Filesystem\Filesystem;
use DTL\Component\Content\Structure\Factory\StructureFactory;
use DTL\Component\Content\Structure\Loader\XmlLoader;

class XmlLoaderTest extends ProphecyTestCase
{
    private $xmlLoader;

    public function setUp()
    {
        parent::setUp();
        $this->xmlLoader = new XmlLoader();
    }

    public function provideLoad()
    {
        return array(
            array(
                __DIR__ . '/data/page/overview.xml',
                array(
                    'parameters' => array(
                        'template' => 'overview.html.twig',
                        'controller' => 'SomeController',
                        'cache_lifetime' => 2400,
                    ),
                    'title' => array(
                        'de' => 'Übersicht',
                        'en' => 'Overview',
                    ),
                    'children' => array(
                        'title' => array(
                            'name' => 'title',
                            'type' => 'text_line',
                            'required' => true,
                            'title' => array(
                                'de' => 'Titel',
                                'en' => 'Title',
                            ),
                            'tags' => array(
                                array(
                                    'name' => 'sulu.search.field',
                                    'attributes' => array(
                                        'role' => 'title',
                                    ),
                                ),
                                array(
                                    'name' => 'sulu.rlp.part',
                                ),
                            ),
                        ),
                        'section' => array(
                            'children' => array(
                                'smartcontent' => array(
                                    'name' => 'smartcontent',
                                    'type' => 'smart_content',
                                    'title' => array(
                                        'de' => 'Smart-Content',
                                        'en' => 'Smart-Content',
                                    ),
                                    'parameters' => array(
                                        'max_per_page' => 5,
                                        'properties' => array(
                                            'title' => 'title',
                                            'article' => 'article',
                                            'ext_title' => 'excerpt.title',
                                            'ext_tags' => 'excerpt.tags',
                                            'ext_images' => 'excerpt.images',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider provideLoad
     */
    public function testLoad($resource, $expectedStructure)
    {
        $structure = $this->xmlLoader->load($resource);
        $this->assertRecursive($structure, $expectedStructure);
    }

    public function assertRecursive($data, $expectedData, $path = 'structure')
    {
        $data = (array) $data;
        foreach ($expectedData as $expectedField => $expectedValue) {
            if (!isset($data[$expectedField])) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not find field "%s" at path: %s.', 
                    $expectedField,
                    $path
                ));
            }

            $actualValue = $data[$expectedField];

            if (is_array($expectedValue)) {
                $this->assertRecursive($actualValue, $expectedValue, $path . ' > ' . $expectedField);
                continue;
            }

            $this->assertEquals($expectedValue, $actualValue, 'at ' . $path);
        }
    }
}
