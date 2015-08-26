<?php
/*
 * This file is part Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;

class AccountDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfiguration()
    {
        $provider = new AccountDataProvider(
            $this->getAccountRepository()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testGetDefaultParameter()
    {
        $provider = new AccountDataProvider(
            $this->getAccountRepository()
        );

        $parameter = $provider->getDefaultPropertyParameter();

        $this->assertEquals([], $parameter);
    }

    public function dataItemsDataProvider()
    {
        $accounts = [
            $this->createAccount('Massive Art'),
            $this->createAccount('Sulu'),
            $this->createAccount('Apple'),
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
        $provider = new AccountDataProvider(
            $this->getAccountRepository($filters, $page, $pageSize, $limit, $repositoryResult)
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
            $this->createAccount('Max', 'Mustermann'),
            $this->createAccount('Erika', 'Mustermann'),
            $this->createAccount('Leon', 'Mustermann'),
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
        $provider = new AccountDataProvider(
            $this->getAccountRepository($filters, $page, $pageSize, $limit, $repositoryResult)
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
        $provider = new AccountDataProvider(
            $this->getAccountRepository()
        );

        $this->assertNull($provider->resolveDatasource('', [], []));
    }

    /**
     * @return AccountRepository
     */
    private function getAccountRepository($filters = [], $page = null, $pageSize = 0, $limit = null, $result = [])
    {
        $mock = $this->prophesize(AccountRepository::class);

        $mock->findByFilters($filters, $page, $pageSize, $limit)->willReturn($result);

        return $mock->reveal();
    }

    private function createAccount($name)
    {
        $account = new Account();
        $account->setName($name);

        return $account;
    }

    private function createDataItem(Account $account)
    {
        return new AccountDataItem($account);
    }

    private function createResourceItem(Account $account)
    {
        $tags = [];
        foreach ($account->getTags() as $tag) {
            $tags[] = $tag->getName();
        }

        return new ArrayAccessItem(
            $account->getId(),
            [
                'number' => $account->getNumber(),
                'name' => $account->getName(),
                'registerNumber' => $account->getNumber(),
                'placeOfJurisdiction' => $account->getPlaceOfJurisdiction(),
                'uid' => $account->getUid(),
                'corporation' => $account->getCorporation(),
                'created' => $account->getCreated(),
                'creator' => $account->getCreator(),
                'changed' => $account->getChanged(),
                'changer' => $account->getChanger(),
                'medias' => $account->getMedias(),
                'emails' => [],
                'phones' => [],
                'faxes' => [],
                'urls' => [],
                'tags' => $tags,
                'categories' => [],
            ],
            $account
        );
    }
}
