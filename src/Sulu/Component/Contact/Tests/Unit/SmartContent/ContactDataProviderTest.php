<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Tests\Unit\SmartContent;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Contact\SmartContent\ContactDataItem;
use Sulu\Component\Contact\SmartContent\ContactDataProvider;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

class ContactDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfiguration()
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new ContactDataProvider(
            $this->getRepository(),
            $serializer->reveal(),
            $referenceStore->reveal()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testGetDefaultParameter()
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new ContactDataProvider(
            $this->getRepository(),
            $serializer->reveal(),
            $referenceStore->reveal()
        );

        $parameter = $provider->getDefaultPropertyParameter();

        $this->assertEquals([], $parameter);
    }

    public function dataItemsDataProvider()
    {
        $contacts = [
            $this->createContact(1, 'Max', 'Mustermann')->reveal(),
            $this->createContact(2, 'Erika', 'Mustermann')->reveal(),
            $this->createContact(3, 'Leon', 'Mustermann')->reveal(),
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
        $serializer = $this->prophesize(SerializerInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new ContactDataProvider(
            $this->getRepository($filters, $page, $pageSize, $limit, $repositoryResult),
            $serializer->reveal(),
            $referenceStore->reveal()
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
    }

    public function testNullSortBy()
    {
        $contacts = [
            $this->createContact(1, 'Max', 'Mustermann')->reveal(),
            $this->createContact(2, 'Erika', 'Mustermann')->reveal(),
            $this->createContact(3, 'Leon', 'Mustermann')->reveal(),
        ];

        $dataItems = [];
        foreach ($contacts as $contact) {
            $dataItems[] = $this->createDataItem($contact);
        }

        $serializer = $this->prophesize(SerializerInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new ContactDataProvider(
            $this->getRepository(['sortBy' => null], 1, null, null, $contacts),
            $serializer->reveal(),
            $referenceStore->reveal()
        );

        $result = $provider->resolveDataItems(['sortBy' => null], [], ['webspace' => 'sulu_io', 'locale' => 'en']);
        $this->assertEquals($dataItems, $result->getItems());
    }

    public function resourceItemsDataProvider()
    {
        $contacts = [
            $this->createContact(1, 'Max', 'Mustermann')->reveal(),
            $this->createContact(2, 'Erika', 'Mustermann')->reveal(),
            $this->createContact(3, 'Leon', 'Mustermann')->reveal(),
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
        $serializeCallback = function(Contact $contact) {
            return $this->serialize($contact);
        };

        $context = SerializationContext::create()->setSerializeNull(true)->setGroups(
            ['fullContact', 'partialAccount', 'partialCategory']
        );

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize(Argument::type(Contact::class), 'array', $context)
            ->will(
                function($args) use ($serializeCallback) {
                    return $serializeCallback($args[0]);
                }
            );

        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new ContactDataProvider(
            $this->getRepository($filters, $page, $pageSize, $limit, $repositoryResult),
            $serializer->reveal(),
            $referenceStore->reveal()
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
    }

    public function testResolveDataSource()
    {
        $serializer = $this->prophesize(SerializerInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new ContactDataProvider(
            $this->getRepository(),
            $serializer->reveal(),
            $referenceStore->reveal()
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

    private function createContact($id, $firstName, $lastName, $tags = [])
    {
        $contact = $this->prophesize(Contact::class);
        $contact->getId()->willReturn($id);
        $contact->getFirstName()->willReturn($firstName);
        $contact->getLastName()->willReturn($lastName);
        $contact->getFullName()->willReturn($firstName . ' ' . $lastName);
        $contact->getTags()->willReturn($tags);
        $contact->getFormOfAddress()->willReturn('');
        $contact->getTitle()->willReturn('');
        $contact->getSalutation()->willReturn('');
        $contact->getMiddleName()->willReturn('');
        $contact->getBirthday()->willReturn(new \DateTime());
        $contact->getCreated()->willReturn(new \DateTime());
        $contact->getChanged()->willReturn(new \DateTime());
        $contact->getMedias()->willReturn([]);

        return $contact;
    }

    private function createDataItem(Contact $contact)
    {
        return new ContactDataItem($contact);
    }

    private function createResourceItem(Contact $contact)
    {
        return new ArrayAccessItem($contact->getId(), $this->serialize($contact), $contact);
    }

    private function serialize(Contact $contact)
    {
        $tags = [];
        foreach ($contact->getTags() as $tag) {
            $tags[] = $tag->getName();
        }

        return [
            'formOfAddress' => $contact->getFormOfAddress(),
            'title' => $contact->getTitle(),
            'salutation' => $contact->getSalutation(),
            'fullName' => $contact->getFullName(),
            'firstName' => $contact->getFirstName(),
            'lastName' => $contact->getLastName(),
            'middleName' => $contact->getMiddleName(),
            'birthday' => $contact->getBirthday(),
            'created' => $contact->getCreated(),
            'changed' => $contact->getChanged(),
            'medias' => $contact->getMedias(),
            'emails' => [],
            'phones' => [],
            'faxes' => [],
            'urls' => [],
            'tags' => $tags,
            'categories' => [],
        ];
    }
}
