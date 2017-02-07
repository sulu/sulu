<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Jackalope\Node;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Content\Types\ContactSelectionContentType;
use Sulu\Bundle\ContactBundle\Util\CustomerIdConverter;
use Sulu\Bundle\ContactBundle\Util\IndexComparator;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeInterface;

class ContactSelectionContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $template = '@TestBundle:Templates:my-template.html.twig';

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
     * @var ContactManagerInterface
     */
    private $contactManager;

    /**
     * @var ContactManagerInterface
     */
    private $accountManager;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var Serializer
     */
    private $serializer;

    protected function setUp()
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

        $this->serializer = $this->prophesize(Serializer::class);
    }

    public function testGetType()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
        );

        $this->assertEquals(ContentTypeInterface::PRE_SAVE, $type->getType());
    }

    public function testGetTemplate()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
        );

        $this->assertEquals($this->template, $type->getTemplate());
    }

    public function testRead()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testReadNull()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testReadPropertyNotExists()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testWrite()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testWriteNull()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testRemove()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testGetViewData()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
        );

        $view = $type->getViewData($this->property->reveal());

        $this->assertEquals([], $view);
    }

    public function testGetDefaultValue()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
        );

        $defaultValue = $type->getDefaultValue();

        $this->assertEquals([], $defaultValue);
    }

    public function testGetDefaultParams()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testHasValue()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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

    public function testGetContentDataOnlyContact()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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
        $this->serializer->serialize($data[0], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[0]
        );
        $this->serializer->serialize($data[1], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[1]
        );
        $this->serializer->serialize($data[2], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[2]
        );
        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(3, $result);
        $this->assertEquals($contact1->reveal(), $result[0]);
        $this->assertEquals($contact2->reveal(), $result[1]);
        $this->assertEquals($contact3->reveal(), $result[2]);
    }

    public function testGetContentDataCombined()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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
        $this->serializer->serialize($data[0], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[0]
        );
        $this->serializer->serialize($data[1], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[1]
        );
        $this->serializer->serialize($data[2], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[2]
        );
        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(3, $result);
        $this->assertEquals($entity1->reveal(), $result[0]);
        $this->assertEquals($entity2->reveal(), $result[1]);
        $this->assertEquals($entity3->reveal(), $result[2]);
    }

    public function testGetContentDataOrderOnlyContact()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
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
        $this->serializer->serialize($data[0], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[0]
        );
        $this->serializer->serialize($data[1], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[1]
        );
        $this->serializer->serialize($data[2], 'array', Argument::type(SerializationContext::class))->willReturn(
            $data[2]
        );
        $contacts = $type->getContentData($this->property->reveal());

        $this->assertCount(3, $contacts);
        $this->assertEquals($contact2->reveal(), $contacts[0]);
        $this->assertEquals($contact1->reveal(), $contacts[1]);
        $this->assertEquals($contact3->reveal(), $contacts[2]);
    }

    public function testGetContentDataEmpty()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
        );

        $this->property->getValue()->willReturn([]);
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->accountManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $result);
    }

    public function testGetContentDataNull()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
        );

        $this->property->getValue()->willReturn(null);
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->accountManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $result);
    }

    public function testGetContentDataWrongType()
    {
        $type = new ContactSelectionContentType(
            $this->template,
            $this->contactManager->reveal(),
            $this->accountManager->reveal(),
            $this->serializer->reveal(),
            new CustomerIdConverter(),
            new IndexComparator()
        );

        $this->property->getValue()->willReturn('blabla');
        $this->contactManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->accountManager->getById(Argument::any(), Argument::any())->shouldNotBeCalled();

        $result = $type->getContentData($this->property->reveal());

        $this->assertCount(0, $result);
    }
}
