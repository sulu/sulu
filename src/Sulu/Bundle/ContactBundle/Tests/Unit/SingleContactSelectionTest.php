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
use Sulu\Bundle\ContactBundle\Content\Types\SingleContactSelection;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\Property;

class SingleContactSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SingleContactSelection
     */
    private $singleContactSelection;

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
    private $contact;

    protected function setUp(): void
    {
        $this->contactRepository = $this->prophesize(ContactRepositoryInterface::class);
        $this->contactReferenceStore = $this->prophesize(ReferenceStore::class);
        $this->contact = $this->prophesize(ContactInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->singleContactSelection = new SingleContactSelection(
            $this->contactRepository->reveal(),
            $this->contactReferenceStore->reveal()
        );
    }

    public function testRead(): void
    {
        $this->node->hasProperty('contact')->willReturn(true);
        $this->node->getPropertyValue('contact')->willReturn(1);

        $this->assertEquals(
            1,
            $this->singleContactSelection->read(
                $this->node->reveal(),
                new Property('contact', [], 'single_contact_selection'),
                'sulu',
                'de',
                ''
            )
        );
    }

    public function testWrite(): void
    {
        $this->node->setProperty('contact', Argument::is(1))->shouldBeCalled();
        $property = new Property('contact', [], 'single_contact_selection');
        $property->setValue(1);

        $this->singleContactSelection->write(
            $this->node->reveal(),
            $property,
            1,
            'sulu',
            'de',
            ''
        );
    }

    public function testWriteNothing(): void
    {
        $this->node->hasProperty('contact')->shouldBeCalled();
        $property = new Property('contact', [], 'single_contact_selection');
        $this->node->hasProperty('contact')->willReturn(true);
        $this->property->remove()->shouldBeCalled();
        $this->node->getProperty('contact')->willReturn($this->property->reveal());

        $this->singleContactSelection->write(
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
        $this->assertEquals(
            [],
            $this->singleContactSelection->getDefaultParams(new Property('contact', [], 'single_contact_selection'))
        );
    }

    public function testViewDataEmpty(): void
    {
        $this->assertEquals(
            [],
            $this->singleContactSelection->getViewData(new Property('contact', [], 'single_contact_selection'))
        );
    }

    public function testViewData(): void
    {
        $property = new Property('contact', [], 'single_contact_selection');
        $property->setValue(1);

        $this->assertEquals(
            [],
            $this->singleContactSelection->getViewData($property)
        );
    }

    public function testContentDataEmpty(): void
    {
        $this->assertNull(
            $this->singleContactSelection->getContentData(new Property('contact', [], 'single_contact_selection'))
        );
    }

    public function testContentData(): void
    {
        $property = new Property('contact', [], 'single_contact_selection');
        $property->setValue(1);
        $this->contactRepository->findById(1)->willReturn($this->contact->reveal())->shouldBeCalled();

        $this->assertEquals($this->contact->reveal(), $this->singleContactSelection->getContentData($property));
    }

    public function testPreResolveEmpty(): void
    {
        $property = new Property('contact', [], 'single_contact_selection');
        $property->setValue(null);

        $this->contactReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleContactSelection->preResolve($property);
    }

    public function testPreResolve(): void
    {
        $property = new Property('contact', [], 'single_contact_selection');
        $property->setValue(22);

        $this->contactReferenceStore->add(22)->shouldBeCalled();

        $this->singleContactSelection->preResolve($property);
    }
}
