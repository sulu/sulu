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
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Content\Types\SingleAccountSelection;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;

class SingleAccountSelectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SingleAccountSelection
     */
    private $singleAccountSelection;

    /**
     * @var ObjectProphecy<AccountManager>
     */
    private $accountManager;

    /**
     * @var ObjectProphecy<ReferenceStore>
     */
    private $accountReferenceStore;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<Account>
     */
    private $account;

    protected function setUp(): void
    {
        $this->accountManager = $this->prophesize(AccountManager::class);
        $this->accountReferenceStore = $this->prophesize(ReferenceStore::class);
        $this->account = $this->prophesize(Account::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->singleAccountSelection = new SingleAccountSelection(
            $this->accountManager->reveal(),
            $this->accountReferenceStore->reveal()
        );
    }

    public function testRead(): void
    {
        $this->node->hasProperty('account')->willReturn(true);
        $this->node->getPropertyValue('account')->willReturn(1);

        $property = new Property('account', [], 'single_account_selection');

        $this->singleAccountSelection->read(
            $this->node->reveal(),
            $property,
            'sulu',
            'de',
            ''
        );

        $this->assertEquals(1, $property->getValue());
    }

    public function testWrite(): void
    {
        $this->node->setProperty('account', 1)->shouldBeCalled();

        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(1);

        $this->singleAccountSelection->write(
            $this->node->reveal(),
            $property,
            1,
            'sulu',
            'de',
            ''
        );
    }

    public function testWriteObjectDeprecated(): void
    {
        $this->node->setProperty('account', 1)->shouldBeCalled();

        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(['id' => 1]);

        $this->singleAccountSelection->write(
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
        $this->node->hasProperty('account')->shouldBeCalled();
        $property = new Property('account', [], 'single_account_selection');
        $this->node->hasProperty('account')->willReturn(true);
        $this->property->remove()->shouldBeCalled();
        $this->node->getProperty('account')->willReturn($this->property->reveal());

        $this->singleAccountSelection->write(
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
            $this->singleAccountSelection->getDefaultParams(new Property('account', [], 'single_account_selection'))
        );
    }

    public function testViewDataEmpty(): void
    {
        $this->assertEquals(
            [],
            $this->singleAccountSelection->getViewData(new Property('account', [], 'single_account_selection'))
        );
    }

    public function testViewData(): void
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(1);

        $this->assertEquals(
            [],
            $this->singleAccountSelection->getViewData($property)
        );
    }

    public function testContentDataEmpty(): void
    {
        $this->assertNull(
            $this->singleAccountSelection->getContentData(new Property('account', [], 'single_account_selection'))
        );
    }

    public function testContentData(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(1);
        $property->setStructure($structure);

        $this->accountManager
             ->getById(1, $property->getStructure()->getLanguageCode())
             ->willReturn($this->account->reveal())->shouldBeCalled();

        $this->assertEquals($this->account->reveal(), $this->singleAccountSelection->getContentData($property));
    }

    public function testContentDataWithNonExistingAccount(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(1);
        $property->setStructure($structure);

        $this->accountManager
            ->getById(1, $property->getStructure()->getLanguageCode())
            ->willThrow(EntityNotFoundException::class)->shouldBeCalled();

        $this->assertNull($this->singleAccountSelection->getContentData($property));
    }

    public function testPreResolveEmpty(): void
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(null);

        $this->accountReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleAccountSelection->preResolve($property);
    }

    public function testPreResolveEmptyArray(): void
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue([]);

        $this->accountReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleAccountSelection->preResolve($property);
    }

    public function testPreResolve(): void
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(22);

        $this->accountReferenceStore->add(22)->shouldBeCalled();

        $this->singleAccountSelection->preResolve($property);
    }
}
