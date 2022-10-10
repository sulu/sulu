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

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Content\Types\ContactSelection;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\Property;

class ContactSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ContactSelection
     */
    private $contactSelection;

    /**
     * @var ObjectProphecy<ContactRepositoryInterface>
     */
    private $contactRepository;

    /**
     * @var ObjectProphecy<ReferenceStore>
     */
    private $contactReferenceStore;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<ContactInterface>
     */
    private $contact1;

    /**
     * @var ObjectProphecy<ContactInterface>
     */
    private $contact2;

    protected function setUp(): void
    {
        $this->contactRepository = $this->prophesize(ContactRepositoryInterface::class);
        $this->contactReferenceStore = $this->prophesize(ReferenceStore::class);
        $this->contact1 = $this->prophesize(ContactInterface::class);
        $this->contact1->getId()->willReturn(123);
        $this->contact2 = $this->prophesize(ContactInterface::class);
        $this->contact2->getId()->willReturn(789);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->contactSelection = new ContactSelection(
            $this->contactRepository->reveal(),
            $this->contactReferenceStore->reveal()
        );
    }

    public function testRead(): void
    {
        $this->node->hasProperty('contacts')->willReturn(true);
        $this->node->getPropertyValue('contacts')->willReturn([123, 789]);

        $this->assertSame(
            [123, 789],
            $this->contactSelection->read(
                $this->node->reveal(),
                new Property('contacts', [], 'contact_selection'),
                'sulu',
                'de',
                ''
            )
        );
    }

    public function testWrite(): void
    {
        $property = new Property('contacts', [], 'contact_selection');
        $property->setValue([123, 789]);

        $this->node->setProperty('contacts', [123, 789])->shouldBeCalled();

        $this->contactSelection->write(
            $this->node->reveal(),
            $property,
            null,
            'sulu',
            'de',
            ''
        );
    }

    public function testWriteNothing(): void
    {
        $property = new Property('contacts', [], 'contact_selection');
        $property->setValue(null);

        $this->node->hasProperty('contacts')->willReturn(true);
        $this->property->remove()->shouldBeCalled();
        $this->node->getProperty('contacts')->willReturn($this->property->reveal());

        $this->contactSelection->write(
            $this->node->reveal(),
            $property,
            null,
            'sulu',
            'de',
            ''
        );
    }

    public function testDefaultParams(): void
    {
        $this->assertSame(
            [],
            $this->contactSelection->getDefaultParams(new Property('contacts', [], 'contact_selection'))
        );
    }

    public function testViewDataEmpty(): void
    {
        $this->assertSame(
            [],
            $this->contactSelection->getViewData(new Property('contacts', [], 'contact_selection'))
        );
    }

    public function testViewData(): void
    {
        $property = new Property('contacts', [], 'contact_selection');
        $property->setValue([123, 789]);

        $this->assertSame(
            [],
            $this->contactSelection->getViewData($property)
        );
    }

    public function testContentDataEmpty(): void
    {
        $this->assertSame(
            [],
            $this->contactSelection->getContentData(new Property('contacts', [], 'contact_selection'))
        );
    }

    public function testContentData(): void
    {
        $property = new Property('contacts', [], 'contact_selection');
        $property->setValue([123, 789]);

        $result = [$this->contact1->reveal(), $this->contact2->reveal()];
        $this->contactRepository->findByIds([123, 789])->willReturn($result);

        $this->assertSame($result, $this->contactSelection->getContentData($property));
    }

    public function testContentDataWithSorting(): void
    {
        $property = new Property('contacts', [], 'contact_selection');
        $property->setValue([789, 123]);

        $this->contactRepository->findByIds([789, 123])
            ->willReturn([$this->contact1->reveal(), $this->contact2->reveal()]);

        $this->assertSame(
            [$this->contact2->reveal(), $this->contact1->reveal()],
            $this->contactSelection->getContentData($property)
        );
    }

    public function testPreResolveEmpty(): void
    {
        $property = new Property('contacts', [], 'contact_selection');
        $property->setValue(null);

        $this->contactReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->contactSelection->preResolve($property);
    }

    public function testPreResolve(): void
    {
        $property = new Property('contacts', [], 'contact_selection');
        $property->setValue([123, 789]);

        $this->contactReferenceStore->add(123)->shouldBeCalled();
        $this->contactReferenceStore->add(789)->shouldBeCalled();

        $this->contactSelection->preResolve($property);
    }
}
