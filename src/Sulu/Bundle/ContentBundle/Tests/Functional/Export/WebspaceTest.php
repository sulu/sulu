<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Export;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Export\WebspaceInterface;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Tests for the Webspace Export class.
 */
class WebspaceTest extends SuluTestCase
{
    /**
     * @var WebspaceInterface
     */
    private $webspaceExporter;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    protected function setUp()
    {
        parent::initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->webspaceExporter = $this->getContainer()->get('sulu_content.export.webspace');
    }

    public function test12Xliff()
    {
        $this->prepareData();
        $exportData = $this->webspaceExporter->getExportData('sulu_io', 'en', '1.2.xliff');

        $expectedResult = [
            'webspaceKey' => 'sulu_io',
            'locale' => 'en',
            'format' => '1.2.xliff',
            'documents' => $this->getExportResultData(),
        ];

        // FIXME ignore home site for testcase
        unset($exportData['documents'][0]);
        $exportData['documents'] = array_values($exportData['documents']);

        // Ignore uuids
        unset($exportData['documents'][0]['uuid']);
        unset($exportData['documents'][1]['uuid']);

        $this->assertEquals(
            $expectedResult,
            $exportData
        );
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareData()
    {
        /** @var \Sulu\Component\Content\Compat\Structure\PageBridge[] $data */
        $data = $this->getDataArray();
        $extensionDataList = $this->getExtensionDataArray();

        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'en', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'en', 1);

        /** @var Extension $extensionManager */
        $extensionManager = $this->getContainer()->get('sulu.content.extension.manager');

        foreach ($extensionDataList as $key => $extensions) {
            foreach ($extensions as $extensionName => $extensionData) {
                /** @var AbstractExtension $extension */
                $extension = $extensionManager->getExtension($data[$key]->getKey(), $extensionName);
                $extension->setLanguageCode('en', 'i18n', $extensionName);

                $this->mapper->saveExtension(
                    $data[$key]->getUuid(),
                    $extensionData,
                    $extensionName,
                    'sulu_io',
                    'en',
                    1
                );
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    private function getDataArray()
    {
        return [
            [
                'title' => 'Test1',
                'url' => '/test-1',
                'article' => 'Lorem Ipsum dolorem apsum',
                'block' => [
                    [
                        'type' => 'type1',
                        'title' => 'Block-Title-1',
                        'article' => 'Block-Article-1-1',
                    ],
                    [
                        'type' => 'type1',
                        'title' => 'Block-Title-1',
                        'article' => 'Block-Article-1-2',
                    ],
                ],
            ],
            [
                'title' => 'Test2',
                'url' => '/test-2',
                'article' => 'asdfasdf',
                'block' => [
                    [
                        'type' => 'type1',
                        'title' => 'Block-Title-2',
                        'article' => 'Block-Article-2-1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getExtensionDataArray()
    {
        return [
            [
                'seo' => [
                    'title' => 'Seo Test1',
                    'description' => 'Seo Description1',
                    'keywords' => 'Seo keywords 1',
                    'canonicalUrl' => '/url-1-1',
                    'noIndex' => '0',
                    'noFollow' => '1',
                    'hideInSitemap' => '1',
                ],
                'excerpt' => [
                    'title' => 'Excerpt Test1',
                    'more' => 'more Test1',
                    'description' => '<p>Test 1</p>',
                    'categories' => [],
                    'tags' => [],
                    'icon' => '',
                    'images' => '',
                ],
            ],
            [
                'seo' => [
                    'title' => 'Seo Test2',
                    'description' => 'Seo Description2',
                    'keywords' => 'Seo keywords 2',
                    'canonicalUrl' => '/url-2-1',
                    'noIndex' => '1',
                    'noFollow' => '0',
                    'hideInSitemap' => '1',
                ],
                'excerpt' => [
                    'title' => 'Excerpt Test2',
                    'more' => 'more Test2',
                    'description' => '<p>Test 2</p>',
                    'categories' => [],
                    'tags' => [],
                    'icon' => '',
                    'images' => '',
                ],
            ],
        ];
    }

    /**
     * @param $name
     * @param string $prefix
     *
     * @return array
     */
    private function getTypeOptionsByName($name, $prefix = '')
    {
        $type = null;
        $translate = null;

        switch ($name) {
            case 'title':
                $type = 'text_line';
                $translate = true;
                break;
            case 'url':
                $type = 'resource_locator';
                $translate = false;
                break;
            case 'type':
                $type = $prefix . '_type';
                $translate = false;
                break;
            case 'article':
                $type = 'text_line';
                $translate = true;
                break;
            case 'block':
                $type = 'block';
                $translate = false;
                break;
        }

        $options = [
            'translate' => $translate,
        ];

        return [$type, $options];
    }

    /**
     * @return array
     */
    private function getExportResultData()
    {
        $pages = $this->getDataArray();
        $extensionDataList = $this->getExtensionDataArray();

        $data = [];
        foreach ($pages as $key => $page) {
            $contentData = [];
            $extensionData = $extensionDataList[$key];

            foreach ($page as $name => $property) {
                if (
                    strpos($name, 'seo') === false
                    && strpos($name, 'excerpt') === false
                ) {
                    if ($name == 'block') {
                        $blockChildren = [];
                        foreach ($property as $block) {
                            $blockPropertyData = [];
                            foreach ($block as $blockName => $blockProperty) {
                                list($type, $options) = $this->getTypeOptionsByName($blockName, $name);
                                $blockPropertyData[$blockName] = $this->createItemArray($blockName, $type, $options, $blockProperty);
                            }
                            $blockChildren[] = $blockPropertyData;
                        }

                        list($type, $options) = $this->getTypeOptionsByName($name);
                        $propertyData = $this->createItemArray($name, $type, $options, null, $blockChildren);
                    } else {
                        list($type, $options) = $this->getTypeOptionsByName($name);
                        $propertyData = $this->createItemArray($name, $type, $options, $property);
                    }

                    $contentData[$name] = $propertyData;
                }
            }

            $extensionData = [
                'seo' => [
                    'title' => $this->createItemArray(
                        'title',
                        '',
                        true,
                        $extensionData['seo']['title']
                    ),
                    'description' => $this->createItemArray(
                        'description',
                        '',
                        true,
                        $extensionData['seo']['description']
                    ),
                    'keywords' => $this->createItemArray(
                        'keywords',
                        '',
                        true,
                        $extensionData['seo']['keywords']
                    ),
                    'canonicalUrl' => $this->createItemArray(
                        'canonicalUrl',
                        '',
                        false,
                        $extensionData['seo']['canonicalUrl']
                    ),
                    'noIndex' => $this->createItemArray('noIndex', '', false, $extensionData['seo']['noIndex']),
                    'noFollow' => $this->createItemArray('noFollow', '', false, $extensionData['seo']['noFollow']),
                    'hideInSitemap' => $this->createItemArray(
                        'hideInSitemap',
                        '',
                        false,
                        $extensionData['seo']['hideInSitemap']
                    ),
                ],
                'excerpt' => [
                    'title' => $this->createItemArray('title', 'text_line', true, $extensionData['excerpt']['title']),
                    'more' => $this->createItemArray('more', 'text_line', true, $extensionData['excerpt']['more']),
                    'description' => $this->createItemArray(
                        'description',
                        'text_editor',
                        true,
                        $extensionData['excerpt']['description']
                    ),
                    'categories' => $this->createItemArray(
                        'categories',
                        'category_list',
                        false,
                        !empty($extensionData['excerpt']['categories']) ? json_encode(
                            $extensionData['excerpt']['categories']
                        ) : ''
                    ),
                    'tags' => $this->createItemArray(
                        'tags',
                        'tag_list',
                        false,
                        !empty($extensionData['excerpt']['tags']) ? json_encode($extensionData['excerpt']['tags']) : ''
                    ),
                    'icon' => $this->createItemArray(
                        'icon',
                        'media_selection',
                        false,
                        $extensionData['excerpt']['icon']
                    ),
                    'images' => $this->createItemArray(
                        'images',
                        'media_selection',
                        false,
                        $extensionData['excerpt']['icon']
                    ),
                ],
            ];

            $data[] = [
                'locale' => 'en',
                'structureType' => 'overview',
                'content' => $contentData,
                'extensions' => $extensionData,
            ];
        }

        return $data;
    }

    /**
     * @param $name
     * @param $type
     * @param $options
     * @param $value
     * @param $children
     *
     * @return array
     */
    private function createItemArray(
        $name,
        $type,
        $options,
        $value = null,
        $children = null
    ) {
        if (is_bool($options)) {
            $options = [
                'translate' => $options,
            ];
        }

        $data = [
            'name' => $name,
            'type' => $type,
            'options' => $options,
        ];

        if ($value !== null) {
            $data['value'] = $value;
        }

        if ($children !== null) {
            $data['children'] = $children;
        }

        return $data;
    }
}
