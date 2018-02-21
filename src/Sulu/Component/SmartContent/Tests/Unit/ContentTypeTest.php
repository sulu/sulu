<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Tests\Unit;

use Prophecy\Argument;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Category\Request\CategoryRequestHandlerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\ContentType as SmartContent;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderPool;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\Exception\PageOutOfBoundsException;
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

    /**
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    /**
     * @var ReferenceStoreInterface
     */
    private $categoryReferenceStore;

    /**
     * @var ReferenceStoreInterface
     */
    private $tagReferenceStore;

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
            ['resolveTagIds', 'resolveTagName']
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

        $this->tagManager->expects($this->any())->method('resolveTagIds')->will(
            $this->returnValueMap(
                [
                    [[1, 2], ['Tag1', 'Tag2']],
                ]
            )
        );

        $this->tagManager->expects($this->any())->method('resolveTagNames')->will(
            $this->returnValueMap(
                [
                    [['Tag1', 'Tag2'], [1, 2]],
                    [['Tag1'], [1]],
                    [['Tag2'], [2]],
                ]
            )
        );

        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);

        $this->categoryReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->tagReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
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
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        $this->assertEquals(
            'SuluContentBundle:Template:content-types/smart_content.html.twig',
            $smartContent->getTemplate()
        );
    }

    public function testWrite()
    {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

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
            PropertyInterface::class,
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

        $smartContent->write($node, $property, 0, 'test', 'en', 's');
    }

    public function testRead()
    {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

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
            PropertyInterface::class,
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

        $smartContent->read($node, $property, 'test', 'en', 's');
    }

    public function testGetViewData()
    {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->prophesize(StructureInterface::class);

        $config = ['dataSource' => 'some-uuid'];
        $parameter = ['max_per_page' => new PropertyParameter('max_per_page', '5')];

        $property->expects($this->at(1))->method('getValue')
            ->willReturn($config);
        $property->expects($this->any())->method('getValue')
            ->willReturn(array_merge($config, ['page' => 1, 'hasNextPage' => true]));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue($parameter));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure->reveal()));

        $this->contentDataProvider->resolveResourceItems(
            [
                'dataSource' => 'some-uuid',
                'page' => 1,
                'hasNextPage' => true,
                'excluded' => ['123-123-123'],
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteTagsOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoriesOperator' => 'OR',
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'alias' => null,
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tags_operator' => new PropertyParameter('website_tags_operator', 'OR'),
                'website_categories_operator' => new PropertyParameter('website_categories_operator', 'OR'),
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
                    'audienceTargeting' => false,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', '5'),
                'deep_link' => new PropertyParameter('deep_link', ''),
                'exclude_duplicates' => new PropertyParameter('exclude_duplicates', false),
            ],
            ['webspaceKey' => 'sulu_io', 'locale' => 'de'],
            null,
            1,
            5
        )->willReturn(new DataProviderResult([1, 2, 3, 4, 5, 6], true));

        $structure->getUuid()->willReturn('123-123-123');
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'))
            ->willReturn(1);

        $viewData = $smartContent->getViewData($property);

        $this->assertContains(array_merge($config, ['page' => 1, 'hasNextPage' => true]), $viewData);
    }

    public function testGetContentData()
    {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        $property = $this->getContentDataProperty(
            [
                'dataSource' => '123-123-123',
                'categories' => [1],
                'websiteCategories' => [2],
                'tags' => [1],
                'websiteTags' => [2],
            ]
        );

        $this->tagRequestHandler->getTags('tags')->willReturn(['Tag2']);
        $this->categoryRequestHandler->getCategories('categories')->willReturn([2]);

        $this->categoryReferenceStore->add(1)->shouldBeCalled();
        $this->categoryReferenceStore->add(2)->shouldBeCalled();
        $this->tagReferenceStore->add(1)->shouldBeCalled();
        $this->tagReferenceStore->add(2)->shouldBeCalled();

        $contentData = $smartContent->getContentData($property);

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

    public function testGetContentDataPaged()
    {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->prophesize(StructureInterface::class);

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'))
            ->willReturn(1);

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue(['dataSource' => '123-123-123']));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', '5')]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure->reveal()));

        $this->contentDataProvider->resolveResourceItems(
            [
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteTagsOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoriesOperator' => 'OR',
                'dataSource' => '123-123-123',
                'excluded' => ['123-123-123'],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'alias' => null,
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tags_operator' => new PropertyParameter('website_tags_operator', 'OR'),
                'website_categories_operator' => new PropertyParameter('website_categories_operator', 'OR'),
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
                    'audienceTargeting' => false,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', '5'),
                'deep_link' => new PropertyParameter('deep_link', ''),
                'exclude_duplicates' => new PropertyParameter('exclude_duplicates', false),
            ],
            ['webspaceKey' => 'sulu_io', 'locale' => 'de'],
            null,
            1,
            5
        )->willReturn(new DataProviderResult([1, 2, 3, 4, 5], true));

        $structure->getUuid()->willReturn('123-123-123');
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');

        $contentData = $smartContent->getContentData($property);

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
            [4, 3, 8, '123-123-123', [], false, PageOutOfBoundsException::class],
            [1, 3, 8, '123-123-123', [], false],
            [-1, 3, 8, '123-123-123', [1, 2, 3], true, PageOutOfBoundsException::class],
            [0, 3, 8, '123-123-123', [1, 2, 3], true, PageOutOfBoundsException::class],
            ['99999999999999999999', 3, 8, '123-123-123', [1, 2, 3], true, PageOutOfBoundsException::class],
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
        $hasNextPage,
        $exception = null
    ) {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        if ($exception) {
            $this->setExpectedException($exception);
        }

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->prophesize(StructureInterface::class);

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'))
            ->willReturn($page);

        $config = ['limitResult' => $limitResult, 'dataSource' => $uuid];

        $this->contentDataProvider->resolveResourceItems(
            [
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteCategories' => [],
                'websiteTagsOperator' => 'OR',
                'websiteCategoriesOperator' => 'OR',
                'limitResult' => $limitResult,
                'dataSource' => $uuid,
                'excluded' => [$uuid],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'alias' => null,
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tags_operator' => new PropertyParameter('website_tags_operator', 'OR'),
                'website_categories_operator' => new PropertyParameter('website_categories_operator', 'OR'),
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
                    'audienceTargeting' => false,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', $pageSize),
                'deep_link' => new PropertyParameter('deep_link', ''),
                'exclude_duplicates' => new PropertyParameter('exclude_duplicates', false),
            ],
            ['webspaceKey' => 'sulu_io', 'locale' => 'de'],
            $limitResult,
            $page,
            $pageSize
        )->willReturn(new DataProviderResult($expectedData, $hasNextPage));

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue($config));
        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', $pageSize)]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure->reveal()));

        $structure->getUuid()->willReturn($uuid);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');

        $contentData = $smartContent->getContentData($property);
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
        $hasNextPage,
        $exception = null
    ) {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig'
        );

        if ($exception) {
            $this->setExpectedException($exception);
        }

        $property = $this->getMockForAbstractClass(
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->prophesize(StructureInterface::class);

        $this->request->expects($this->at(0))->method('get')
            ->with($this->equalTo('p'))
            ->willReturn($page);

        $config = ['limitResult' => $limitResult, 'dataSource' => $uuid];

        $this->contentDataProvider->resolveResourceItems(
            [
                'tags' => [],
                'categories' => [],
                'websiteTags' => [],
                'websiteTagsOperator' => 'OR',
                'websiteCategories' => [],
                'websiteCategoriesOperator' => 'OR',
                'limitResult' => $limitResult,
                'dataSource' => $uuid,
                'page' => $page,
                'hasNextPage' => $hasNextPage,
                'excluded' => [$uuid],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'alias' => null,
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_categories_operator' => new PropertyParameter('website_categories_operator', 'OR'),
                'website_tags_operator' => new PropertyParameter('website_tags_operator', 'OR'),
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
                    'audienceTargeting' => false,
                ],
                'datasource' => null,
                'max_per_page' => new PropertyParameter('max_per_page', $pageSize),
                'deep_link' => new PropertyParameter('deep_link', null),
                'exclude_duplicates' => new PropertyParameter('exclude_duplicates', false),
            ],
            ['webspaceKey' => 'sulu_io', 'locale' => 'de'],
            $limitResult,
            $page < 1 ? 1 : ($page > PHP_INT_MAX ? PHP_INT_MAX : $page),
            $pageSize
        )->willReturn(new DataProviderResult($expectedData, $hasNextPage));

        $property->expects($this->at(1))->method('getValue')
            ->willReturn($config);
        $property->expects($this->any())->method('getValue')
            ->willReturn(array_merge($config, ['page' => $page, 'hasNextPage' => $hasNextPage]));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue(['max_per_page' => new PropertyParameter('max_per_page', $pageSize)]));
        $property->expects($this->exactly(3))->method('getStructure')
            ->will($this->returnValue($structure->reveal()));

        $structure->getUuid()->willReturn($uuid);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');

        $viewData = $smartContent->getViewData($property);
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
                    'categoryRoot' => null,
                    'categoriesParameter' => 'categories',
                    'tagsParameter' => 'tags',
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
            PropertyInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getValue', 'getParams']
        );
        $structure = $this->prophesize(StructureInterface::class);

        $property->expects($this->exactly(1))->method('getValue')
            ->will($this->returnValue($value));

        $property->expects($this->any())->method('getParams')
            ->will($this->returnValue([]));

        $property->expects($this->any())->method('getStructure')
            ->will($this->returnValue($structure->reveal()));

        $this->contentDataProvider->resolveResourceItems(
            [
                'categories' => array_key_exists('categories', $value) ? $value['categories'] : [],
                'websiteCategories' => array_key_exists('websiteCategories', $value) ? $value['websiteCategories'] : [],
                'websiteCategoriesOperator' => 'OR',
                'tags' => array_key_exists('tags', $value) ? $value['tags'] : [],
                'websiteTags' => array_key_exists('websiteTags', $value) ? $value['websiteTags'] : [],
                'websiteTagsOperator' => 'OR',
                'dataSource' => $value['dataSource'],
                'excluded' => [$value['dataSource']],
            ],
            [
                'provider' => new PropertyParameter('provider', 'content'),
                'alias' => null,
                'page_parameter' => new PropertyParameter('page_parameter', 'p'),
                'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
                'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
                'website_tags_operator' => new PropertyParameter('website_tags_operator', 'OR'),
                'website_categories_operator' => new PropertyParameter('website_categories_operator', 'OR'),
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
                    'audienceTargeting' => false,
                ],
                'datasource' => null,
                'deep_link' => new PropertyParameter('deep_link', null),
                'exclude_duplicates' => new PropertyParameter('exclude_duplicates', false),
            ],
            ['webspaceKey' => 'sulu_io', 'locale' => 'de'],
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
                true
            )
        );

        $structure->getUuid()->willReturn('123-123-123');
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de');

        return $property;
    }

    public function testGetContentDataWithActivatedAudienceTargeting()
    {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig',
            $this->targetGroupStore->reveal()
        );

        $property = $this->prophesize(PropertyInterface::class);
        $property->getParams()->willReturn([
            'provider' => new PropertyParameter('provider', 'content'),
        ]);
        $property->getValue()->willReturn([
            'audienceTargeting' => true,
        ]);

        $structure = $this->prophesize(StructureInterface::class);
        $property->getStructure()->willReturn($structure->reveal());

        $this->targetGroupStore->getTargetGroupId()->willReturn(1);
        $this->contentDataProvider->resolveResourceItems(
            Argument::that(function($value) {
                return 1 === $value['targetGroupId'];
            }),
            Argument::cetera()
        )->willReturn(new DataProviderResult([], false));

        $property->setValue(Argument::that(function($value) {
            return 1 === $value['targetGroupId'];
        }))->shouldBeCalled();

        $smartContent->getContentData($property->reveal());
    }

    public function testGetContentDataWithDeactivatedAudienceTargeting()
    {
        $smartContent = new SmartContent(
            $this->dataProviderPool,
            $this->tagManager,
            $this->requestStack,
            $this->tagRequestHandler->reveal(),
            $this->categoryRequestHandler->reveal(),
            $this->categoryReferenceStore->reveal(),
            $this->tagReferenceStore->reveal(),
            'SuluContentBundle:Template:content-types/smart_content.html.twig',
            $this->targetGroupStore->reveal()
        );

        $property = $this->prophesize(PropertyInterface::class);
        $property->getParams()->willReturn([
            'provider' => new PropertyParameter('provider', 'content'),
        ]);
        $property->getValue()->willReturn([
            'audienceTargeting' => false,
        ]);

        $structure = $this->prophesize(StructureInterface::class);
        $property->getStructure()->willReturn($structure->reveal());

        $this->targetGroupStore->getTargetGroupId()->shouldNotBeCalled();
        $this->contentDataProvider->resolveResourceItems(
            Argument::that(function($value) {
                return !array_key_exists('targetGroupId', $value);
            }),
            Argument::cetera()
        )->willReturn(new DataProviderResult([], false));

        $property->setValue(Argument::that(function($value) {
            return !array_key_exists('targetGroupId', $value);
        }))->shouldBeCalled();

        $smartContent->getContentData($property->reveal());
    }
}
