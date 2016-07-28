<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Tests\Unit\SmartContent;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Component\Contact\SmartContent\AccountDataItem;
use Sulu\Component\Contact\SmartContent\AccountDataProvider;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

class AccountDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfiguration()
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $provider = new AccountDataProvider(
            $this->getRepository(),
            $serializer->reveal()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testGetDefaultParameter()
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $provider = new AccountDataProvider(
            $this->getRepository(),
            $serializer->reveal()
        );

        $parameter = $provider->getDefaultPropertyParameter();

        $this->assertEquals([], $parameter);
    }

    public function dataItemsDataProvider()
    {
        $accounts = [
            $this->createAccount(1, 'Massive Art')->reveal(),
            $this->createAccount(2, 'Sulu')->reveal(),
            $this->createAccount(3, 'Apple')->reveal(),
        ];

        $dataItems = [];
        foreach ($accounts as $account) {
            $dataItems[] = $this->createDataItem($account);
        }

        return [
            [['tags' => [1]], null, 1, 3, $accounts, false, $dataItems],
            [['tags' => [1]], null, 1, 2, $accounts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 5, 1, 2, $accounts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 1, 1, 2, array_slice($accounts, 0, 1), false, array_slice($dataItems, 0, 1)],
        ];
    }

    /**
     * @dataProvider dataItemsDataProvider
     */
    public function testResolveDataItems($filters, $limit, $page, $pageSize, $repositoryResult, $hasNextPage, $items)
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $provider = new AccountDataProvider(
            $this->getRepository($filters, $page, $pageSize, $limit, $repositoryResult),
            $serializer->reveal()
        );

        $result = $provider->resolveDataItems(
            $filters,
            [],
            ['webspace' => 'sulu_io', 'locale' => 'en'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
        $this->assertEquals([], $result->getReferencedUuids());
    }

    public function resourceItemsDataProvider()
    {
        $accounts = [
            $this->createAccount(1, 'Massive Art')->reveal(),
            $this->createAccount(2, 'Sulu')->reveal(),
            $this->createAccount(3, 'Apple')->reveal(),
        ];

        $dataItems = [];
        foreach ($accounts as $account) {
            $dataItems[] = $this->createResourceItem($account);
        }

        return [
            [['tags' => [1]], null, 1, 3, $accounts, false, $dataItems],
            [['tags' => [1]], null, 1, 2, $accounts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 5, 1, 2, $accounts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 1, 1, 2, array_slice($accounts, 0, 1), false, array_slice($dataItems, 0, 1)],
        ];
    }

    /**
     * @dataProvider resourceItemsDataProvider
     */
    public function testResolveResourceItems(
        $filters,
        $limit,
        $page,
        $pageSize,
        $repositoryResult,
        $hasNextPage,
        $items
    ) {
        $serializeCallback = function (Account $account) {
            return $this->serialize($account);
        };

        $context = SerializationContext::create()->setSerializeNull(true)->setGroups(
            ['fullAccount', 'partialContact', 'partialCategory']
        );

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize(Argument::type(Account::class), 'array', $context)
            ->will(
                function ($args) use ($serializeCallback) {
                    return $serializeCallback($args[0]);
                }
            );

        $provider = new AccountDataProvider(
            $this->getRepository($filters, $page, $pageSize, $limit, $repositoryResult),
            $serializer->reveal()
        );

        $result = $provider->resolveResourceItems(
            $filters,
            [],
            ['webspace' => 'sulu_io', 'locale' => 'en'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
        $this->assertEquals([], $result->getReferencedUuids());
    }

    public function testResolveDataSource()
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $provider = new AccountDataProvider(
            $this->getRepository(),
            $serializer->reveal()
        );

        $this->assertNull($provider->resolveDatasource('', [], []));
    }

    /**
     * @return DataProviderRepositoryInterface
     */
    private function getRepository(
        $filters = [],
        $page = null,
        $pageSize = 0,
        $limit = null,
        $result = [],
        $options = []
    ) {
        $mock = $this->prophesize(DataProviderRepositoryInterface::class);

        $mock->findByFilters($filters, $page, $pageSize, $limit, 'en', $options)->willReturn($result);

        return $mock->reveal();
    }

    private function createAccount($id, $name, $tags = [])
    {
        $account = $this->prophesize(Account::class);
        $account->getId()->willReturn($id);
        $account->getNumber()->willReturn($id);
        $account->getName()->willReturn($name);
        $account->getTags()->willReturn($tags);
        $account->getPlaceOfJurisdiction()->willReturn('');
        $account->getUid()->willReturn('');
        $account->getCorporation()->willReturn('');
        $account->getCreated()->willReturn(new \DateTime());
        $account->getChanged()->willReturn(new \DateTime());
        $account->getMedias()->willReturn([]);

        return $account;
    }

    private function createDataItem(Account $account)
    {
        return new AccountDataItem($account);
    }

    private function createResourceItem(Account $account)
    {
        return new ArrayAccessItem($account->getId(), $this->serialize($account), $account);
    }

    private function serialize(Account $account)
    {
        $tags = [];
        foreach ($account->getTags() as $tag) {
            $tags[] = $tag->getName();
        }

        return [
            'number' => $account->getNumber(),
            'name' => $account->getName(),
            'registerNumber' => $account->getNumber(),
            'placeOfJurisdiction' => $account->getPlaceOfJurisdiction(),
            'uid' => $account->getUid(),
            'corporation' => $account->getCorporation(),
            'created' => $account->getCreated(),
            'changed' => $account->getChanged(),
            'medias' => $account->getMedias(),
            'emails' => [],
            'phones' => [],
            'faxes' => [],
            'urls' => [],
            'tags' => $tags,
            'categories' => [],
        ];
    }
}
