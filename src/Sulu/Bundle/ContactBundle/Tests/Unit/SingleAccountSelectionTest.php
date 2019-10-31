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
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Content\Types\SingleAccountSelection;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;

class SingleAccountSelectionTest extends TestCase
{
    /**
     * @var SingleAccountSelection
     */
    private $singleAccountSelection;

    /**
     * @var AccountManager
     */
    private $accountManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $accountReferenceStore;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var
     */
    private $property;

    /**
     * @var AccountInterface
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

    public function testRead()
    {
        $this->node->hasProperty('account')->willReturn(true);
        $this->node->getPropertyValue('account')->willReturn(1);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(['id' => 1]);
        $property->setStructure($structure);

        $this->account->getId()->willReturn(1);
        $this->account->getName()->willReturn('Sulu');

        $this->accountManager
             ->getById(1, $property->getStructure()->getLanguageCode())
             ->willReturn($this->account->reveal())->shouldBeCalled();

        $this->singleAccountSelection->read(
            $this->node->reveal(),
            $property,
            'sulu',
            'de',
            ''
        );

        $this->assertEquals(
            [
                'id' => 1,
                'name' => 'Sulu',
            ],
            $property->getValue()
        );
    }

    public function testReadWithNonExistingAccount()
    {
        $this->node->hasProperty('account')->willReturn(true);
        $this->node->getPropertyValue('account')->willReturn(1);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(['id' => 1]);
        $property->setStructure($structure);

        $this->account->getId()->willReturn(1);
        $this->account->getName()->willReturn('Sulu');

        $this->accountManager
             ->getById(1, $property->getStructure()->getLanguageCode())
             ->willThrow(EntityNotFoundException::class)->shouldBeCalled();

        $this->singleAccountSelection->read(
            $this->node->reveal(),
            $property,
            'sulu',
            'de',
            ''
        );

        $this->assertNull($property->getValue());
    }

    public function testWrite()
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

    public function testWriteNothing()
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

    public function testDefaultParams()
    {
        $this->assertEquals(
            [],
            $this->singleAccountSelection->getDefaultParams(new Property('account', [], 'single_account_selection'))
        );
    }

    public function testViewDataEmpty()
    {
        $this->assertEquals(
            [],
            $this->singleAccountSelection->getViewData(new Property('account', [], 'single_account_selection'))
        );
    }

    public function testViewData()
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(1);

        $this->assertEquals(
            [],
            $this->singleAccountSelection->getViewData($property)
        );
    }

    public function testContentDataEmpty()
    {
        $this->assertNull(
            $this->singleAccountSelection->getContentData(new Property('account', [], 'single_account_selection'))
        );
    }

    public function testContentData()
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(['id' => 1]);
        $property->setStructure($structure);

        $this->accountManager
             ->getById(1, $property->getStructure()->getLanguageCode())
             ->willReturn($this->account->reveal())->shouldBeCalled();

        $this->assertEquals($this->account->reveal(), $this->singleAccountSelection->getContentData($property));
    }

    public function testPreResolveEmpty()
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(null);

        $this->accountReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleAccountSelection->preResolve($property);
    }

    public function testPreResolveEmptyArray()
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue([]);

        $this->accountReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->singleAccountSelection->preResolve($property);
    }

    public function testPreResolve()
    {
        $property = new Property('account', [], 'single_account_selection');
        $property->setValue(['id' => 22]);

        $this->accountReferenceStore->add(22)->shouldBeCalled();

        $this->singleAccountSelection->preResolve($property);
    }
}
