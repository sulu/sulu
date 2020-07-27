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
use Sulu\Bundle\ContactBundle\Content\Types\AccountSelection;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class AccountSelectionTest extends TestCase
{
    /**
     * @var AccountSelection
     */
    private $accountSelection;

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
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var Account
     */
    private $account1;

    /**
     * @var Account
     */
    private $account2;

    protected function setUp(): void
    {
        $this->accountManager = $this->prophesize(AccountManager::class);
        $this->accountReferenceStore = $this->prophesize(ReferenceStore::class);
        $this->account1 = $this->prophesize(Account::class);
        $this->account2 = $this->prophesize(Account::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->accountSelection = new AccountSelection(
            $this->accountManager->reveal(),
            $this->accountReferenceStore->reveal()
        );
    }

    public function testRead()
    {
        $this->node->hasProperty('accounts')->willReturn(true);
        $this->node->getPropertyValue('accounts')->willReturn([123, 789]);

        $this->assertEquals(
            [123, 789],
            $this->accountSelection->read(
                $this->node->reveal(),
                new Property('accounts', [], 'account_selection'),
                'sulu',
                'de',
                ''
            )
        );
    }

    public function testWrite()
    {
        $property = new Property('accounts', [], 'account_selection');
        $property->setValue([123, 789]);

        $this->node->setProperty('accounts', [123, 789])->shouldBeCalled();

        $this->accountSelection->write(
            $this->node->reveal(),
            $property,
            null,
            'sulu',
            'de',
            ''
        );
    }

    public function testWriteNothing()
    {
        $property = new Property('accounts', [], 'account_selection');
        $property->setValue(null);

        $this->node->hasProperty('accounts')->willReturn(true);
        $this->property->remove()->shouldBeCalled();
        $this->node->getProperty('accounts')->willReturn($this->property->reveal());

        $this->accountSelection->write(
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
            $this->accountSelection->getDefaultParams(new Property('accounts', [], 'account_selection'))
        );
    }

    public function testViewDataEmpty()
    {
        $this->assertEquals(
            [],
            $this->accountSelection->getViewData(new Property('accounts', [], 'account_selection'))
        );
    }

    public function testViewData()
    {
        $property = new Property('accounts', [], 'account_selection');
        $property->setValue([123, 789]);

        $this->assertEquals(
            [],
            $this->accountSelection->getViewData($property)
        );
    }

    public function testContentDataEmpty()
    {
        $this->assertEquals(
            [],
            $this->accountSelection->getContentData(new Property('accounts', [], 'account_selection'))
        );
    }

    public function testContentData()
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = new Property('accounts', [], 'account_selection');
        $property->setValue([123, 789]);
        $property->setStructure($structure->reveal());

        $result = [$this->account1->reveal(), $this->account2->reveal()];
        $this->accountManager->getByIds([123, 789], 'de')->willReturn($result);

        $this->assertEquals($result, $this->accountSelection->getContentData($property));
    }

    public function testPreResolveEmpty()
    {
        $property = new Property('accounts', [], 'account_selection');
        $property->setValue(null);

        $this->accountReferenceStore->add(Argument::any())->shouldNotBeCalled();

        $this->accountSelection->preResolve($property);
    }

    public function testPreResolve()
    {
        $property = new Property('accounts', [], 'account_selection');
        $property->setValue([123, 789]);

        $this->accountReferenceStore->add(123)->shouldBeCalled();
        $this->accountReferenceStore->add(789)->shouldBeCalled();

        $this->accountSelection->preResolve($property);
    }
}
