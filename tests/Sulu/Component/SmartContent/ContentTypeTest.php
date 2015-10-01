<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Category\Request\CategoryRequestHandlerInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\ContentType as SmartContent;
use Sulu\Component\Tag\Request\TagRequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

//FIXME remove on update to phpunit 3.8, caused by https://github.com/sebastianbergmann/phpunit/issues/604
interface MyNodeInterface extends \PHPCR\NodeInterface, \Iterator
{
}

/**
 * @group unit
 */
class ContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SmartContent
     */
    private $smartContent;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TagRequestHandlerInterface
     */
    private $tagRequestHandler;

    /**
     * @var CategoryRequestHandlerInterface
     */
    private $categoryRequestHandler;

    /**
     * @var DataProviderPoolInterface
     */
    private $dataProviderPool;

    /**
     * @var DataProviderInterface
     */
    private $contentDataProvider;

    public function setUp()
    {
        $this->contentDataProvider = $this->prophesize(DataProviderInterface::class);
        $this->contentDataProvider->getConfiguration()->willReturn($this->getProviderConfiguration());
        $this->contentDataProvider->getDefaultPropertyParameter()->willReturn([]);

        $this->dataProviderPool = new DataProviderPool();
        $this->dataProviderPool->add('content', $this->contentDataProvider->reveal());

        $this->tagManager = $this->getMockForAbstractClass(
            TagManagerInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['resolveTagIds', 'resolveTagNames']
        );

        $this->requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $this->request = $this->getMockBuilder(Request::class)->getMock();

        $this->requestStack->expects($this->any())->method('getCurrentRequest')->will(
            $this->returnValue($this->request)
        );

        $this->tagRequestHandler = $this->prophesize(TagRequestHandlerInterface::class);
        $this->tagRequestHandler->getTags('tags')->willReturn([]);

        $this->categoryRequestHandler = $this->prophesize(CategoryRequestHandlerInterface::class);
        $this->categoryRequestHandler->getCategories('categories')->willReturn([]);

        $this->smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        $this->tagManager->expects($this->any())->method('resolveTagIds')->will(
            $this->returnValueMap(
                [
                    [[1, 2], ['Tag1', 'Tag2']],
                ]
            )
        );

        $this->tagManager->expects($this->any())->method('resolveTagName')->will(
            $this->returnValueMap(
                [
                    [['Tag1', 'Tag2'], [1, 2]],
                ]
            )
        );
    }

    private function getProviderConfiguration()
    {
        $configuration = new ProviderConfiguration();
        $configuration->setTags(true);
        $configuration->setCategories(false);
        $configuration->setLimit(true);
        $configuration->setPresentAs(true);
        $configuration->setPaginated(true);

        return $configuration;
    }

    public function testTemplate()
    {
        $this->assertEquals(
            'SuluContentBundle:Template:content-types/smart_content.html.twig',
            $this->smartContent->getTemplate()
        );
    }

    public function testWrite()
    {
        $node = $this->getMockForAbstractClass(
            MyNodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setProperty']
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue']
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                [
                    'dataSource' => [
                        'home/products',
                    ],
                    'sortBy' => [
                        'published',
                    ],
                ]
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                [
                    'dataSource' => [
                        'home/products',
                    ],
                    'sortBy' => [
                        'published',
                    ],
                ]
            )
        );

        $this->smartContent->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $config = [
            'tags' => ['Tag1', 'Tag2'],
            'limitResult' => '2',
        ];

        $node = $this->getMockForAbstractClass(
            MyNodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['setValue']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    ['property', '{}', '{"tags":[1,2],"limitResult":"2"}'],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));
        $property->expects($this->any())->method('getParams')->will(
            $this->returnValue(['properties' => ['my_title' => 'title']])
        );

        $property->expects($this->exactly(1))->method('setValue')->with($config);

        $this->smartContent->read($node, $property, 'test', 'en', 's');
    }

    public function testReadPreview()
    {
        $config = [
            'tags' => ['Tag1', 'Tag2'],
            'limitResult' => '2',
        ];

        $node = $this->getMockForAbstractClass(
            MyNodeInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPropertyValueWithDefault']
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['setValue']
        );

        $node->expects($this->any())->method('getPropertyValueWithDefault')->will(
            $this->returnValueMap(
                [
                    ['property', '{}', '{"tags":[1,2],"limitResult":"2"}'],
                ]
            )
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));
        $property->expects($this->any())->method('getParams')->will($this->returnValue([]));

        $property->expects($this->exactly(1))->method('setValue')->with($config);

        $this->smartContent->readForPreview(
            ['tags' => ['Tag1', 'Tag2'], 'limitResult' => 2],
            $property,
            'test',
            'en',
            's'
        );
    }

    public function testGetViewData()
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $config = ['dataSource' => 'some-uuid'];
        $parameter = ['max_per_page' => new PropertyParameter('max_per_page', '5')];

        $property->expects($this->at(1))->method('getValue')
            ->willReturn($config);
        $property->expects($this->any())->method('getValue')
            ->willReturn(array_merge($config, ['page' => 1, 'hasNextPage' => true]));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue($parameter));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $this->contentDataProvider->resolveResourceItems(
            [
                'dataSource' => 'some-uuid',
                'page' => 1,
                'hasNextPage' => true,
                'excluded' => ['123-123-123'],
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tag_operator' => new PropertyParameter('website_tag_operator', 'OR'),
                'website_category_operator' => new PropertyParameter('website_category_operator', 'OR'),
                'sorting' => new PropertyParameter('sorting', [], 'collection'),
                'present_as' => new PropertyParameter('present_as', [], 'collection'),
                'category_root' => new PropertyParameter('category_root', null),
                'display_options' => new PropertyParameter(
                    'display_options',
                    [
                        'tags' => new PropertyParameter('tags', true),
                        'categories' => new PropertyParameter('categories', true),
                        'sorting' => new PropertyParameter('sorting', true),
                        'limit' => new PropertyParameter('limit', true),
                        'presentAs' => new PropertyParameter('presentAs', true),
                    ],
                    'collection'
                ),
                'has' => [
                    'datasource' => false,
                    'tags' => true,
                    'categories' => false,
                    'sorting' => false,
                    'limit' => true,
                    'presentAs' => true,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', '5'),
            ],
            ['webspaceKey' => null, 'locale' => null],
            null,
            1,
            5
        )->willReturn(new DataProviderResult([1, 2, 3, 4, 5, 6], true, [1, 2, 3, 4, 5, 6]));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue('123-123-123'));

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'), $this->equalTo(1), $this->equalTo(false))
            ->willReturn(1);

        $viewData = $this->smartContent->getViewData($property);

        $this->assertContains(array_merge($config, ['page' => 1, 'hasNextPage' => true]), $viewData);
    }

    public function testGetContentData()
    {
        $property = $this->getContentDataProperty();
        $contentData = $this->smartContent->getContentData($property);

        $this->assertEquals(
            [
                ['uuid' => 1],
                ['uuid' => 2],
                ['uuid' => 3],
                ['uuid' => 4],
                ['uuid' => 5],
                ['uuid' => 6],
            ],
            $contentData
        );
    }

    public function testGetReferencedUuids()
    {
        $property = $this->getContentDataProperty(
            ['tags' => [], 'dataSource' => '123-123-123', 'referencedUuids' => [1, 2, 3, 4, 5, 6]]
        );
        $uuids = $this->smartContent->getReferencedUuids($property);

        $this->assertEquals([1, 2, 3, 4, 5, 6], $uuids);
    }

    public function testGetContentDataPaged()
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'), $this->equalTo(1), $this->equalTo(false))
            ->willReturn(1);

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue(['dataSource' => '123-123-123']));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', '5')]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $this->contentDataProvider->resolveResourceItems(
            [
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'dataSource' => '123-123-123',
                'excluded' => ['123-123-123'],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tag_operator' => new PropertyParameter('website_tag_operator', 'OR'),
                'website_category_operator' => new PropertyParameter('website_category_operator', 'OR'),
                'sorting' => new PropertyParameter('sorting', [], 'collection'),
                'present_as' => new PropertyParameter('present_as', [], 'collection'),
                'category_root' => new PropertyParameter('category_root', null),
                'display_options' => new PropertyParameter(
                    'display_options',
                    [
                        'tags' => new PropertyParameter('tags', true),
                        'categories' => new PropertyParameter('categories', true),
                        'sorting' => new PropertyParameter('sorting', true),
                        'limit' => new PropertyParameter('limit', true),
                        'presentAs' => new PropertyParameter('presentAs', true),
                    ],
                    'collection'
                ),
                'has' => [
                    'datasource' => false,
                    'tags' => true,
                    'categories' => false,
                    'sorting' => false,
                    'limit' => true,
                    'presentAs' => true,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', '5'),
            ],
            ['webspaceKey' => null, 'locale' => null],
            null,
            1,
            5
        )->willReturn(new DataProviderResult([1, 2, 3, 4, 5], true, [1, 2, 3, 4, 5]));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue('123-123-123'));

        $contentData = $this->smartContent->getContentData($property);

        $this->assertEquals([1, 2, 3, 4, 5], $contentData);
    }

    public function pageProvider()
    {
        return [
            // first page page-size 3 (one page more to check available pages)
            [1, 3, 8, '123-123-123', [1, 2, 3], true],
            // second page page-size 3 (one page more to check available pages)
            [2, 3, 8, '123-123-123', [4, 5, 6], true],
            // third page page-size 3 (only two pages because of the limit-result)
            [3, 3, 8, '123-123-123', [7, 8], false],
            // fourth page page-size 3 (empty result)
            [4, 3, 8, '123-123-123', [], false],
        ];
    }

    /**
     * @dataProvider pageProvider
     */
    public function testGetContentDataPagedLimit(
        $page,
        $pageSize,
        $limitResult,
        $uuid,
        $expectedData,
        $hasNextPage
    ) {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'), $this->equalTo(1), $this->equalTo(false))
            ->willReturn($page);

        $config = ['limitResult' => $limitResult, 'dataSource' => $uuid];

        $this->contentDataProvider->resolveResourceItems(
            [
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteCategories' => [],
                'websiteTagOperator' => 'OR',
                'websiteCategoryOperator' => 'OR',
                'limitResult' => $limitResult,
                'dataSource' => $uuid,
                'excluded' => [$uuid],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tag_operator' => new PropertyParameter('website_tag_operator', 'OR'),
                'website_category_operator' => new PropertyParameter('website_category_operator', 'OR'),
                'sorting' => new PropertyParameter('sorting', [], 'collection'),
                'present_as' => new PropertyParameter('present_as', [], 'collection'),
                'category_root' => new PropertyParameter('category_root', null),
                'display_options' => new PropertyParameter(
                    'display_options',
                    [
                        'tags' => new PropertyParameter('tags', true),
                        'categories' => new PropertyParameter('categories', true),
                        'sorting' => new PropertyParameter('sorting', true),
                        'limit' => new PropertyParameter('limit', true),
                        'presentAs' => new PropertyParameter('presentAs', true),
                    ],
                    'collection'
                ),
                'has' => [
                    'datasource' => false,
                    'tags' => true,
                    'categories' => false,
                    'sorting' => false,
                    'limit' => true,
                    'presentAs' => true,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', $pageSize),
            ],
            ['webspaceKey' => null, 'locale' => null],
            $limitResult,
            $page,
            $pageSize
        )->willReturn(new DataProviderResult($expectedData, $hasNextPage, $expectedData));

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue($config));
        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', $pageSize)]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue($uuid));

        $contentData = $this->smartContent->getContentData($property);
        $this->assertEquals($expectedData, $contentData);
    }

    /**
     * @dataProvider pageProvider
     */
    public function testGetViewDataPagedLimit(
        $page,
        $pageSize,
        $limitResult,
        $uuid,
        $expectedData,
        $hasNextPage
    ) {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'), $this->equalTo(1), $this->equalTo(false))
            ->willReturn($page);

        $config = ['limitResult' => $limitResult, 'dataSource' => $uuid];

        $this->contentDataProvider->resolveResourceItems(
            [
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'limitResult' => $limitResult,
                'dataSource' => $uuid,
                'page' => $page,
                'hasNextPage' => $hasNextPage,
                'excluded' => [$uuid],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_category_operator' => new PropertyParameter('website_category_operator', 'OR'),
                'website_tag_operator' => new PropertyParameter('website_tag_operator', 'OR'),
                'sorting' => new PropertyParameter('sorting', [], 'collection'),
                'present_as' => new PropertyParameter('present_as', [], 'collection'),
                'category_root' => new PropertyParameter('category_root', null),
                'display_options' => new PropertyParameter(
                    'display_options',
                    [
                        'tags' => new PropertyParameter('tags', true),
                        'categories' => new PropertyParameter('categories', true),
                        'sorting' => new PropertyParameter('sorting', true),
                        'limit' => new PropertyParameter('limit', true),
                        'presentAs' => new PropertyParameter('presentAs', true),
                    ],
                    'collection'
                ),
                'has' => [
                    'datasource' => false,
                    'tags' => true,
                    'categories' => false,
                    'sorting' => false,
                    'limit' => true,
                    'presentAs' => true,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', $pageSize),
            ],
            ['webspaceKey' => null, 'locale' => null],
            $limitResult,
            $page,
            $pageSize
        )->willReturn(new DataProviderResult($expectedData, $hasNextPage, $expectedData));

        $property->expects($this->at(1))->method('getValue')
            ->willReturn($config);
        $property->expects($this->any())->method('getValue')
            ->willReturn(array_merge($config, ['page' => $page, 'hasNextPage' => $hasNextPage]));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', $pageSize)]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure));

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue($uuid));

        $viewData = $this->smartContent->getViewData($property);
        $this->assertEquals(
            array_merge(
                [
                    'dataSource' => null,
                    'includeSubFolders' => null,
                    'category' => null,
                    'tags' => [],
                    'sortBy' => null,
                    'sortMethod' => null,
                    'presentAs' => null,
                    'limitResult' => null,
                    'page' => null,
                    'hasNextPage' => null,
                    'paginated' => false,
                    'referencedUuids' => [],
                    'categoryRoot' => null,
                ],
                $config,
                ['page' => $page, 'hasNextPage' => $hasNextPage]
            ),
            $viewData
        );
    }

    private function getContentDataProperty($value = ['dataSource' => '123-123-123'])
    {
        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\PropertyInterface',
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Compat\StructureInterface'
        );

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue($value));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue([]));

        $property->expects($this->any())->method('getStructure')
            ->will($this->returnValue($structure));

        $this->contentDataProvider->resolveResourceItems(
            [
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteTagOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoryOperator' => 'OR',
                'dataSource' => '123-123-123',
                'excluded' => ['123-123-123'],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tag_operator' => new PropertyParameter('website_tag_operator', 'OR'),
                'website_category_operator' => new PropertyParameter('website_category_operator', 'OR'),
                'sorting' => new PropertyParameter('sorting', [], 'collection'),
                'present_as' => new PropertyParameter('present_as', [], 'collection'),
                'category_root' => new PropertyParameter('category_root', null),
                'display_options' => new PropertyParameter(
                    'display_options',
                    [
                        'tags' => new PropertyParameter('tags', true),
                        'categories' => new PropertyParameter('categories', true),
                        'sorting' => new PropertyParameter('sorting', true),
                        'limit' => new PropertyParameter('limit', true),
                        'presentAs' => new PropertyParameter('presentAs', true),
                    ],
                    'collection'
                ),
                'has' => [
                    'datasource' => false,
                    'tags' => true,
                    'categories' => false,
                    'sorting' => false,
                    'limit' => true,
                    'presentAs' => true,
                ],
                'datasource' => null,
            ],
            ['webspaceKey' => null, 'locale' => null],
            null
        )->willReturn(
            new DataProviderResult(
                [
                    ['uuid' => 1],
                    ['uuid' => 2],
                    ['uuid' => 3],
                    ['uuid' => 4],
                    ['uuid' => 5],
                    ['uuid' => 6],
                ],
                true,
                [1, 2, 3, 4, 5, 6]
            )
        );

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue('123-123-123'));

        return $property;
    }
}
