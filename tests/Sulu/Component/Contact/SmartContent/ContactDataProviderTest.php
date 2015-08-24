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

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;

class ContactDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfiguration()
    {
        $provider = new ContactDataProvider(
            $this->getContactRepository()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testGetDefaultParameter()
    {
        $provider = new ContactDataProvider(
            $this->getContactRepository()
        );

        $parameter = $provider->getDefaultPropertyParameter();

        $this->assertEquals([], $parameter);
    }

    public function dataItemsDataProvider()
    {
        $contacts = [
            $this->createContact('Max', 'Mustermann'),
            $this->createContact('Erika', 'Mustermann'),
            $this->createContact('Leon', 'Mustermann'),
        ];

        $dataItems = [];
        foreach ($contacts as $contact) {
            $dataItems[] = $this->createDataItem($contact);
        }

        return [
            [['tags' => [1]], null, 1, 3, $contacts, false, $dataItems],
            [['tags' => [1]], null, 1, 2, $contacts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 5, 1, 2, $contacts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 1, 1, 2, array_slice($contacts, 0, 1), false, array_slice($dataItems, 0, 1)],
        ];
    }

    /**
     * @dataProvider dataItemsDataProvider
     */
    public function testResolveDataItems($filters, $limit, $page, $pageSize, $repositoryResult, $hasNextPage, $items)
    {
        $provider = new ContactDataProvider(
            $this->getContactRepository($filters, $page, $pageSize, $limit, $repositoryResult)
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
        $contacts = [
            $this->createContact('Max', 'Mustermann'),
            $this->createContact('Erika', 'Mustermann'),
            $this->createContact('Leon', 'Mustermann'),
        ];

        $dataItems = [];
        foreach ($contacts as $contact) {
            $dataItems[] = $this->createResourceItem($contact);
        }

        return [
            [['tags' => [1]], null, 1, 3, $contacts, false, $dataItems],
            [['tags' => [1]], null, 1, 2, $contacts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 5, 1, 2, $contacts, true, array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 1, 1, 2, array_slice($contacts, 0, 1), false, array_slice($dataItems, 0, 1)],
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
        $provider = new ContactDataProvider(
            $this->getContactRepository($filters, $page, $pageSize, $limit, $repositoryResult)
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
        $provider = new ContactDataProvider(
            $this->getContactRepository()
        );

        $this->assertNull($provider->resolveDatasource('', [], []));
    }

    /**
     * @return ContactRepository
     */
    private function getContactRepository($filters = [], $page = null, $pageSize = 0, $limit = null, $result = [])
    {
        $mock = $this->prophesize(ContactRepository::class);

        $mock->findByFilters($filters, $page, $pageSize, $limit)->willReturn($result);

        return $mock->reveal();
    }

    private function createContact($firstName, $lastName)
    {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);

        return $contact;
    }

    private function createDataItem(Contact $contact)
    {
        return new ContactDataItem($contact);
    }

    private function createResourceItem(Contact $contact)
    {
        $tags = [];
        foreach ($contact->getTags() as $tag) {
            $tags[] = $tag->getName();
        }

        return new ArrayAccessItem(
            $contact->getId(),
            [
                'formOfAddress' => $contact->getFormOfAddress(),
                'title' => $contact->getTitle(),
                'salutation' => $contact->getSalutation(),
                'fullName' => $contact->getFullName(),
                'firstName' => $contact->getFirstName(),
                'lastName' => $contact->getLastName(),
                'middleName' => $contact->getMiddleName(),
                'birthday' => $contact->getBirthday(),
                'created' => $contact->getCreated(),
                'creator' => $contact->getCreator(),
                'changed' => $contact->getChanged(),
                'changer' => $contact->getChanger(),
                'medias' => $contact->getMedias(),
                'emails' => [],
                'phones' => [],
                'faxes' => [],
                'urls' => [],
                'tags' => $tags,
                'categories' => [],
            ],
            $contact
        );
    }
}
