<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AccountRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    public function setUp(): void
    {
        $this->em = $this->getEntityManager();
        $this->accountRepository = $this->em->getRepository(Account::class);
        $this->purgeDatabase();
    }

    public function testFindByIds(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $this->em->flush();

        $result = $this->accountRepository->findByIds([$account1->getId(), $account2->getId()]);

        $this->assertCount(2, $result);
        $this->assertEquals('Sulu', $result[0]->getName());
        $this->assertEquals('Sensiolabs', $result[1]->getName());
    }

    public function testFindByNotExistingIds(): void
    {
        $result = $this->accountRepository->findByIds([15, 99]);

        $this->assertCount(0, $result);
    }

    public function testFindByIdsEmpty(): void
    {
        $result = $this->accountRepository->findByIds([]);

        $this->assertCount(0, $result);
    }

    public function testRemoveParentAccount(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');
        $account2->setParent($account1);

        $this->em->flush();
        $account1Id = $account1->getId();

        $this->em->remove($account1);
        $this->em->flush();

        $this->assertNull($this->em->find(AccountInterface::class, $account1Id));
    }

    private function createAccount($name, $tags = [], $categories = [])
    {
        $account = new Account();
        $account->setName($name);

        foreach ($tags as $tag) {
            $account->addTag($tag);
        }

        foreach ($categories as $category) {
            $account->addCategory($category);
        }

        $this->em->persist($account);

        return $account;
    }
}
