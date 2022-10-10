<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Jackalope\Node;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Content\Types\ContactAccountSelection;
use Sulu\Bundle\ContactBundle\Util\CustomerIdConverter;
use Sulu\Bundle\ContactBundle\Util\IndexComparator;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;

class ContactAccountSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_test';

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * @var int
     */
    private $userId = 1;

    /**
     * @var string
     */
    private $segmentKey = 'winter';

    /**
     * @var ObjectProphecy<ContactManagerInterface>
     */
    private $contactManager;

    /**
     * @var ObjectProphecy<ContactManagerInterface>
     */
    private $accountManager;

    /**
     * @var ObjectProphecy<Node>
     */
    private $node;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<StructureInterface>
     */
    private $structure;

    /**
     * @var ObjectProphecy<ArraySerializerInterface>
     */
    private $serializer;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $accountReferenceStore;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $contactReferenceStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactManager = $this->prophesize(ContactManagerInterface::class);
        $this->accountManager = $this->prophesize(ContactManagerInterface::class);
        $this->node = $this->prophesize(Node::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->structure = $this->prophesize(StructureInterface::class);

        $this->structure->getLanguageCode()->willReturn($this->locale);
        $this->structure->getWebspaceKey()->willReturn($this->webspaceKey);

        $this->property->getStructure()->willReturn($this->structure->reveal());

        $this->serializer = $this->prophesize(ArraySerializerInterface::class);
        $this->accountReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->contactReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
    }

    public function testRead(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(true);
        $this->node->getPropertyValue('test', null)->willReturn([1, 2, 3]);
        $this->property->setValue([1, 2, 3])->shouldBeCalled();

        $type->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testReadNull(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(true);
        $this->node->getPropertyValue('test', null)->willReturn(null);
        $this->property->setValue([])->shouldBeCalled();

        $type->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testReadPropertyNotExists(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(false);
        $this->node->getPropertyValue(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->property->setValue([])->shouldBeCalled();

        $type->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testWrite(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getName()->willReturn('test');
        $this->property->getValue()->willReturn([1, 2, 3]);
        $this->node->setProperty('test', [1, 2, 3])->shouldBeCalled();

        $type->write(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->userId,
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testWriteNull(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getName()->willReturn('test');
        $this->property->getValue()->willReturn(null);
        $this->property->setValue(Argument::any())->shouldNotBeCalled();
        $this->node->setProperty('test', [])->shouldBeCalled();

        $type->write(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->userId,
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testRemove(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $nodeProperty = $this->prophesize(\PHPCR\PropertyInterface::class);
        $nodeProperty->remove()->shouldBeCalled();
        $nodeProperty->setValue(Argument::any())->shouldNotBeCalled();
        $nodeProperty->getValue(Argument::any())->shouldNotBeCalled();

        $this->property->getName()->willReturn('test');
        $this->property->getValue()->shouldNotBeCalled();
        $this->node->hasProperty('test')->willReturn(true);
        $this->node->getProperty('test')->willReturn($nodeProperty->reveal());
        $this->node->setProperty(Argument::any(), Argument::any())->shouldNotBeCalled();

        $type->remove(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            $this->segmentKey
        );
    }

    public function testGetViewData(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $view = $type->getViewData($this->property->reveal());

        $this->assertEquals([], $view);
    }

    public function testGetDefaultValue(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $defaultValue = $type->getDefaultValue();

        $this->assertEquals([], $defaultValue);
    }

    public function testGetDefaultParams(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $defaultParams = $type->getDefaultParams();

        $this->assertEquals(
            [
                'contact' => new PropertyParameter('contact', true),
                'account' => new PropertyParameter('account', true),
            ],
            $defaultParams
        );
    }

    public function testHasValue(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getName()->willReturn('test');
        $this->node->hasProperty('test')->willReturn(true);

        $this->assertTrue(
            $type->hasValue(
                $this->node->reveal(),
                $this->property->reveal(),
                $this->webspaceKey,
                $this->locale,
                $this->segmentKey
            )
        );
    }

    public function testGetContentDataOnlyContact(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $contact1 = $this->prophesize(Contact::class);
        $contact2 = $this->prophesize(Contact::class);
        $contact3 = $this->prophesize(Contact::class);

        $contact1->getId()->willReturn(1);
        $contact2->getId()->willReturn(2);
        $contact3->getId()->willReturn(3);

        $data = [$contact1->reveal(), $contact2->reveal(), $contact3->reveal()];

        $this->property->getValue()->willReturn(['c1', 'c2', 'c3']);
        $this->contactManager->getByIds([1, 2, 3], $this->locale)->willReturn($data);
        $this->accountManager->getByIds([], $this->locale)->willReturn([]);
        $this->serializer->serialize($data[0], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 1]
        );
        $this->serializer->serialize($data[1], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 2]
        );
        $this->serializer->serialize($data[2], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 3]
        );
        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 1], $result[0]);
        $this->assertEquals(['id' => 2], $result[1]);
        $this->assertEquals(['id' => 3], $result[2]);
    }

    public function testGetContentDataCombined(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $entity1 = $this->prophesize(Account::class);
        $entity2 = $this->prophesize(Contact::class);
        $entity3 = $this->prophesize(Account::class);

        $entity1->getId()->willReturn(1);
        $entity2->getId()->willReturn(1);
        $entity3->getId()->willReturn(3);

        $data = [$entity1->reveal(), $entity2->reveal(), $entity3->reveal()];

        $this->property->getValue()->willReturn(['a1', 'c1', 'a3']);
        $this->contactManager->getByIds([1], $this->locale)->willReturn([$entity2]);
        $this->accountManager->getByIds([1, 3], $this->locale)->willReturn([$entity1, $entity3]);
        $this->serializer->serialize($data[0], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 1]
        );
        $this->serializer->serialize($data[1], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 1]
        );
        $this->serializer->serialize($data[2], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 2]
        );
        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(3, $result);

        $this->assertEquals(['id' => 1], $result[0]);
        $this->assertEquals(['id' => 1], $result[1]);
        $this->assertEquals(['id' => 2], $result[2]);
    }

    public function testGetContentDataOrderOnlyContact(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $contact1 = $this->prophesize(Contact::class);
        $contact2 = $this->prophesize(Contact::class);
        $contact3 = $this->prophesize(Contact::class);

        $contact1->getId()->willReturn(1);
        $contact2->getId()->willReturn(2);
        $contact3->getId()->willReturn(3);

        $dataUnsorted = [$contact1->reveal(), $contact2->reveal(), $contact3->reveal()];
        $data = [$contact2->reveal(), $contact1->reveal(), $contact3->reveal()];

        $this->property->getValue()->willReturn(['c2', 'c1', 'c3']);
        $this->contactManager->getByIds([2, 1, 3], $this->locale)->willReturn($dataUnsorted);
        $this->accountManager->getByIds([], $this->locale)->willReturn([]);
        $this->serializer->serialize($data[0], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 2]
        );
        $this->serializer->serialize($data[1], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 1]
        );
        $this->serializer->serialize($data[2], Argument::type(SerializationContext::class))->willReturn(
            ['id' => 3]
        );
        $contacts = $type->getContentData($this->property->reveal());

        $this->assertCount(3, $contacts);
        $this->assertEquals(['id' => 2], $contacts[0]);
        $this->assertEquals(['id' => 1], $contacts[1]);
        $this->assertEquals(['id' => 3], $contacts[2]);
    }

    public function testGetContentDataEmpty(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getValue()->willReturn([]);
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->accountManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $result);
    }

    public function testGetContentDataNull(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getValue()->willReturn(null);
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->accountManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $result);
    }

    public function testGetContentDataWrongType(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getValue()->willReturn('blabla');
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->accountManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $result);
    }

    public function testPreResolve(): void
    {
        $type = new ContactAccountSelection(
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator(),
            $this->accountReferenceStore->reveal(),
            $this->contactReferenceStore->reveal()
        );

        $this->property->getValue()->willReturn(['a1', 'c1', 'a3']);
        $type->preResolve($this->property->reveal());

        $this->accountReferenceStore->add(1)->shouldBeCalled();
        $this->accountReferenceStore->add(3)->shouldBeCalled();
        $this->contactReferenceStore->add(1)->shouldBeCalled();
    }
}
