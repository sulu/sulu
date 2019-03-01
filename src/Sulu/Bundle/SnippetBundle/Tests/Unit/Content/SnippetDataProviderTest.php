<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Content;

use Prophecy\Argument;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\VirtualProxyInterface;
use Sulu\Bundle\SnippetBundle\Content\SnippetDataProvider;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Util\SuluNodeHelper;

class SnippetDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $snippetQueryBuilder;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentManagerInterface
     */
    private $referenceStore;

    /**
     * @var SnippetDataProvider
     */
    private $snippetDataProvider;

    public function setUp()
    {
        $this->contentQueryExecutor = $this->prophesize(ContentQueryExecutorInterface::class);
        $this->snippetQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->nodeHelper = $this->prophesize(SuluNodeHelper::class);
        $this->proxyFactory = $this->prophesize(LazyLoadingValueHolderFactory::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->proxyFactory->createProxy(Argument::cetera())
            ->willReturn($this->prophesize(VirtualProxyInterface::class)->reveal());

        $this->snippetDataProvider = new SnippetDataProvider(
            $this->contentQueryExecutor->reveal(),
            $this->snippetQueryBuilder->reveal(),
            $this->nodeHelper->reveal(),
            $this->proxyFactory->reveal(),
            $this->documentManager->reveal(),
            $this->referenceStore->reveal()
        );

        $this->referenceStore->getAll()->willReturn([]);
    }

    /**
     * @dataProvider provideResolveDataItems
     */
    public function testResolveDataItems(
        $filters,
        $propertyParameter,
        $options,
        $limit,
        $page,
        $pageSize,
        $result,
        $hasNextPage
    ) {
        $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder->reveal(),
            true,
            -1,
            $limit ?: ($pageSize ? $pageSize + 1 : null),
            $pageSize ? $pageSize * ($page - 1) : null
        )->willReturn($result);

        if (array_key_exists('type', $propertyParameter)) {
            $this->nodeHelper->getBaseSnippetUuid($propertyParameter['type'])->willReturn('some-uuid');
        } else {
            $this->nodeHelper->getBaseSnippetUuid(null)->willReturn(null);
        }

        $this->snippetQueryBuilder->init([
            'config' => [
                'excluded' => null,
                'dataSource' => array_key_exists('type', $propertyParameter) ? 'some-uuid' : null,
                'includeSubFolders' => true,
            ],
            'properties' => [],
            'excluded' => [],
        ])->shouldBeCalled();

        $dataProviderResult = $this->snippetDataProvider->resolveDataItems(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );

        $this->assertCount(count($result), $dataProviderResult->getItems());
        $this->assertEquals($hasNextPage, $dataProviderResult->getHasNextPage());
    }

    public function provideResolveDataItems()
    {
        return [
            [['excluded' => null], [], ['webspaceKey' => 'sulu', 'locale' => 'de'], null, 1, null, [], false],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                1,
                null,
                [['uuid' => 1], ['uuid' => 2]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                1,
                1,
                null,
                [['uuid' => 1]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                5,
                2,
                [['uuid' => 1], ['uuid' => 2]],
                false,
            ],
            [
                ['excluded' => null],
                ['type' => 'default'],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                5,
                2,
                [['uuid' => 1], ['uuid' => 2]],
                false,
            ],
        ];
    }

    /**
     * @dataProvider provideResolveDataItems
     */
    public function testResolveResourceItems(
        $filters,
        $propertyParameter,
        $options,
        $limit,
        $page,
        $pageSize,
        $result,
        $hasNextPage
    ) {
        foreach ($result as $item) {
            $this->referenceStore->add($item['uuid'])->shouldBeCalled();
        }

        $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder->reveal(),
            true,
            -1,
            $limit ?: ($pageSize ? $pageSize + 1 : null),
            $pageSize ? $pageSize * ($page - 1) : null
        )->willReturn($result);

        $dataProviderResult = $this->snippetDataProvider->resolveResourceItems(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize
        );

        $this->assertCount(count($result), $dataProviderResult->getItems());
        $this->assertEquals($hasNextPage, $dataProviderResult->getHasNextPage());
    }

    /**
     * @dataProvider provideResolveExcludeDuplicates
     */
    public function testResolveResourceItemsExcludeDuplicates($filters, $uuids)
    {
        $options = ['webspaceKey' => 'sulu', 'locale' => 'de'];

        $this->referenceStore->getAll()->willReturn(['456-456-456']);

        $result = [];
        foreach ($uuids as $uuid) {
            $result[] = ['uuid' => $uuid];
            $this->referenceStore->add($uuid)->shouldBeCalled();
        }

        $this->snippetQueryBuilder->init(
            [
                'config' => array_merge($filters, ['dataSource' => null, 'includeSubFolders' => true]),
                'properties' => [],
                'excluded' => ['456-456-456'],
            ]
        )->shouldBeCalled();

        $this->contentQueryExecutor->execute(
            $options['webspaceKey'],
            [$options['locale']],
            $this->snippetQueryBuilder->reveal(),
            true,
            -1,
            null,
            null
        )->willReturn($result);

        $dataProviderResult = $this->snippetDataProvider->resolveResourceItems(
            $filters,
            ['exclude_duplicates' => new PropertyParameter('exclude_duplicates', true)],
            $options
        );

        $this->assertCount(count($result), $dataProviderResult->getItems());
        $this->assertEquals(false, $dataProviderResult->getHasNextPage());
    }

    public function provideResolveExcludeDuplicates()
    {
        return [
            [
                [],
                ['123-123-123', '321-321-321'],
            ],
            [
                ['excluded' => null],
                ['123-123-123', '321-321-321'],
            ],
            [
                ['excluded' => []],
                ['123-123-123', '321-321-321'],
            ],
        ];
    }

    public function provideResolveResourceItems()
    {
        return [
            [['excluded' => null], [], ['webspaceKey' => 'sulu', 'locale' => 'de'], null, 1, null, [], false],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                1,
                null,
                [['uuid' => 1], ['uuid' => 2]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                1,
                1,
                null,
                [['uuid' => 1]],
                false,
            ],
            [
                ['excluded' => null],
                [],
                ['webspaceKey' => 'sulu', 'locale' => 'de'],
                null,
                5,
                2,
                [['uuid' => 1], ['uuid' => 2]],
                false,
            ],
        ];
    }
}
