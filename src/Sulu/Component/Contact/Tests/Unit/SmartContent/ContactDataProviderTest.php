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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Contact\SmartContent\ContactDataItem;
use Sulu\Component\Contact\SmartContent\ContactDataProvider;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

class ContactDataProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DataProviderRepositoryInterface>
     */
    private $dataProviderRepository;

    /**
     * @var ObjectProphecy<ArraySerializerInterface>
     */
    private $serializer;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    /**
     * @var ContactDataProvider
     */
    private $contactDataProvider;

    public function setUp(): void
    {
        $this->dataProviderRepository = $this->prophesize(DataProviderRepositoryInterface::class);
        $this->serializer = $this->prophesize(ArraySerializerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->contactDataProvider = new ContactDataProvider(
            $this->dataProviderRepository->reveal(),
            $this->serializer->reveal(),
            $this->referenceStore->reveal()
        );
    }

    public function testGetConfiguration(): void
    {
        $configuration = $this->contactDataProvider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testGetDefaultParameter(): void
    {
        $parameter = $this->contactDataProvider->getDefaultPropertyParameter();

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
            [['tags' => [1]], null, 1, 2, $contacts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 5, 1, 2, $contacts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 1, 1, 2, \array_slice($contacts, 0, 1), false, \array_slice($dataItems, 0, 1)],
        ];
    }

    /**
     * @dataProvider dataItemsDataProvider
     */
    public function testResolveDataItems($filters, $limit, $page, $pageSize, $repositoryResult, $hasNextPage, $items): void
    {
        $this->dataProviderRepository->findByFilters(
            $filters,
            $page,
            $pageSize,
            $limit,
            'en',
            [],
            null,
            null
        )->willReturn($repositoryResult);

        $result = $this->contactDataProvider->resolveDataItems(
            $filters,
            [],
            ['locale' => 'en'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
    }

    public function testNullSortBy(): void
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

        $this->dataProviderRepository->findByFilters(
            ['sortBy' => null],
            1,
            null,
            null,
            'en',
            [],
            null,
            null
        )->willReturn($contacts);

        $result = $this->contactDataProvider->resolveDataItems(['sortBy' => null], [], ['webspace' => 'sulu_io', 'locale' => 'en']);
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
            [['tags' => [1]], null, 1, 2, $contacts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 5, 1, 2, $contacts, true, \array_slice($dataItems, 0, 2)],
            [['tags' => [1]], 1, 1, 2, \array_slice($contacts, 0, 1), false, \array_slice($dataItems, 0, 1)],
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
    ): void {
        $serializeCallback = function(Contact $contact) {
            return $this->serialize($contact);
        };

        $context = SerializationContext::create()->setSerializeNull(true)->setGroups(
            ['fullContact', 'partialAccount', 'partialCategory']
        );

        $this->serializer->serialize(Argument::type(Contact::class), $context)
            ->will(
                function($args) use ($serializeCallback) {
                    return $serializeCallback($args[0]);
                }
            );

        $this->dataProviderRepository->findByFilters(
            $filters,
            $page,
            $pageSize,
            $limit,
            'en',
            [],
            null,
            null
        )->willReturn($repositoryResult);

        $result = $this->contactDataProvider->resolveResourceItems(
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

    public function testResolveDataSource(): void
    {
        $this->assertNull($this->contactDataProvider->resolveDatasource('', [], []));
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
