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

    public function testFindByNoPagination(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters([], null, 0, null, 'de');

        $this->assertEquals([$account1, $account2], $result);
    }

    public function testFindByPage1NoLimit(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters([], 1, 2, null, 'de');

        // One more element is returned in order to determine if next page is available
        $this->assertEquals([$account1, $account2, $account3], $result);
    }

    public function testFindByPage2NoLimit(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters([], 2, 2, null, 'de');

        $this->assertEquals([$account3, $account4], $result);
    }

    public function testFindByLimit3(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters([], null, 0, 3, 'de');

        $this->assertEquals([$account1, $account2, $account3], $result);
    }

    public function testFindByPage1Limit5(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft');
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters([], 1, 3, 5, 'de');

        $this->assertEquals([$account1, $account2, $account3, $account4], $result);
    }

    public function testFindByPage2Limit5(): void
    {
        $account1 = $this->createAccount('Sulu');
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft');
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters([], 2, 3, 5, 'de');

        $this->assertEquals([$account4, $account5], $result);
    }

    public function testFindByTagOr(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $account1 = $this->createAccount('Sulu', [$tag1]);
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft', [$tag2]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['tags' => [$tag1, $tag2], 'tagOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByTagAnd(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $account1 = $this->createAccount('Sulu', [$tag1, $tag2]);
        $account2 = $this->createAccount('Sensiolabs', [$tag1]);
        $account3 = $this->createAccount('Google', [$tag2]);
        $account4 = $this->createAccount('Microsoft', [$tag2, $tag1]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['tags' => [$tag1, $tag2], 'tagOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByPage1TagOr(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $account1 = $this->createAccount('Sulu', [$tag1]);
        $account2 = $this->createAccount('Sensiolabs', [$tag1]);
        $account3 = $this->createAccount('Google', [$tag1]);
        $account4 = $this->createAccount('Microsoft');
        $account5 = $this->createAccount('Apple', [$tag1]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['tags' => [$tag1], 'tagOperator' => 'or'],
            1,
            2,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account2, $account3], $result);
    }

    public function testFindByWebsiteTagOr(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $account1 = $this->createAccount('Sulu', [$tag1]);
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft', [$tag2]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteTags' => [$tag1, $tag2], 'websiteTagsOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByWebsiteTagAnd(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $account1 = $this->createAccount('Sulu', [$tag1, $tag2]);
        $account2 = $this->createAccount('Sensiolabs', [$tag1]);
        $account3 = $this->createAccount('Google', [$tag2]);
        $account4 = $this->createAccount('Microsoft', [$tag2, $tag1]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteTags' => [$tag1, $tag2], 'websiteTagsOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByTagAndWebsiteTag(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $account1 = $this->createAccount('Sulu', [$tag1, $tag2]);
        $account2 = $this->createAccount('Sensiolabs', [$tag1]);
        $account3 = $this->createAccount('Google', [$tag2]);
        $account4 = $this->createAccount('Microsoft', [$tag2, $tag1]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteTags' => [$tag1], 'websiteTagsOperator' => 'and', 'tags' => [$tag2], 'tagOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByTagAnd2WebsiteTag(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $tag3 = $this->createTag('Tag 3');
        $account1 = $this->createAccount('Sulu', [$tag1, $tag2, $tag3]);
        $account2 = $this->createAccount('Sensiolabs', [$tag1]);
        $account3 = $this->createAccount('Google', [$tag2]);
        $account4 = $this->createAccount('Microsoft', [$tag2, $tag1]);
        $account5 = $this->createAccount('Apple', [$tag1, $tag3]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteTags' => [$tag1, $tag3], 'websiteTagsOperator' => 'and', 'tags' => [$tag2], 'tagOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1], $result);
    }

    public function testFindBy2TagAndWebsiteTag(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $tag3 = $this->createTag('Tag 3');
        $account1 = $this->createAccount('Sulu', [$tag1, $tag2, $tag3]);
        $account2 = $this->createAccount('Sensiolabs', [$tag1]);
        $account3 = $this->createAccount('Google', [$tag2]);
        $account4 = $this->createAccount('Microsoft', [$tag2, $tag1]);
        $account5 = $this->createAccount('Apple', [$tag1, $tag3]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteTags' => [$tag1], 'websiteTagsOperator' => 'or', 'tags' => [$tag2, $tag3], 'tagOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4, $account5], $result);
    }

    public function testFindByCategoryOr(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $account1 = $this->createAccount('Sulu', [], [$category1]);
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft', [], [$category2]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['categories' => [$category1, $category2], 'categoryOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByCategoryAnd(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $account1 = $this->createAccount('Sulu', [], [$category1, $category2]);
        $account2 = $this->createAccount('Sensiolabs', [], [$category1]);
        $account3 = $this->createAccount('Google', [], [$category2]);
        $account4 = $this->createAccount('Microsoft', [], [$category2, $category1]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['categories' => [$category1, $category2], 'categoryOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByPage1CategoryOr(): void
    {
        $category1 = $this->createCategory('Category 1');
        $account1 = $this->createAccount('Sulu', [], [$category1]);
        $account2 = $this->createAccount('Sensiolabs', [], [$category1]);
        $account3 = $this->createAccount('Google', [], [$category1]);
        $account4 = $this->createAccount('Microsoft');
        $account5 = $this->createAccount('Apple', [], [$category1]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['categories' => [$category1], 'categoryOperator' => 'or'],
            1,
            2,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account2, $account3], $result);
    }

    public function testFindByWebsiteCategoryOr(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $account1 = $this->createAccount('Sulu', [], [$category1]);
        $account2 = $this->createAccount('Sensiolabs');
        $account3 = $this->createAccount('Google');
        $account4 = $this->createAccount('Microsoft', [], [$category2]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteCategories' => [$category1, $category2], 'websiteCategoriesOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByWebsiteCategoryAnd(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $account1 = $this->createAccount('Sulu', [], [$category1, $category2]);
        $account2 = $this->createAccount('Sensiolabs', [], [$category1]);
        $account3 = $this->createAccount('Google', [], [$category2]);
        $account4 = $this->createAccount('Microsoft', [], [$category2, $category1]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteCategories' => [$category1, $category2], 'websiteCategoriesOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByCategoryAndWebsiteCategory(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $account1 = $this->createAccount('Sulu', [], [$category1, $category2]);
        $account2 = $this->createAccount('Sensiolabs', [], [$category1]);
        $account3 = $this->createAccount('Google', [], [$category2]);
        $account4 = $this->createAccount('Microsoft', [], [$category2, $category1]);
        $account5 = $this->createAccount('Apple');

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteCategories' => [$category1], 'websiteCategoriesOperator' => 'and', 'categories' => [$category2], 'categoryOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByCategoryAnd2WebsiteCategory(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $category3 = $this->createCategory('Category 3');
        $account1 = $this->createAccount('Sulu', [], [$category1, $category2, $category3]);
        $account2 = $this->createAccount('Sensiolabs', [], [$category1]);
        $account3 = $this->createAccount('Google', [], [$category2]);
        $account4 = $this->createAccount('Microsoft', [], [$category2, $category1]);
        $account5 = $this->createAccount('Apple', [], [$category1, $category3]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteCategories' => [$category1, $category3], 'websiteCategoriesOperator' => 'and', 'categories' => [$category2], 'categoryOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1], $result);
    }

    public function testFindBy2CategoryAndWebsiteCategory(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $category3 = $this->createCategory('Category 3');
        $account1 = $this->createAccount('Sulu', [], [$category1, $category2, $category3]);
        $account2 = $this->createAccount('Sensiolabs', [], [$category1]);
        $account3 = $this->createAccount('Google', [], [$category2]);
        $account4 = $this->createAccount('Microsoft', [], [$category2, $category1]);
        $account5 = $this->createAccount('Apple', [], [$category1, $category3]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['websiteCategories' => [$category1], 'websiteCategoriesOperator' => 'or', 'categories' => [$category2, $category3], 'categoryOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4, $account5], $result);
    }

    public function testFindByCategoryAndTag(): void
    {
        $category1 = $this->createCategory('Category 1');
        $tag1 = $this->createTag('Tag 1');
        $account1 = $this->createAccount('Sensiolabs', [$tag1], [$category1]);
        $account2 = $this->createAccount('Google', [$tag1], []);
        $account3 = $this->createAccount('Microsoft', [], [$category1]);
        $account4 = $this->createAccount('Apple', [$tag1], [$category1]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            ['categories' => [$category1], 'categoryOperator' => 'or', 'tags' => [$tag1], 'tagOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account4], $result);
    }

    public function testFindByWebsiteCategoryAndWebsiteTag(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $account1 = $this->createAccount('Sensiolabs', [$tag1, $tag2], [$category1, $category2]);
        $account2 = $this->createAccount('Google', [$tag1, $tag2], []);
        $account3 = $this->createAccount('Microsoft', [], [$category1, $category2]);
        $account4 = $this->createAccount('Apple', [$tag1], [$category1]);
        $account5 = $this->createAccount('Sulu', [$tag1, $tag2], [$category1, $category2]);

        $this->em->flush();

        $result = $this->accountRepository->findByFilters(
            [
                'categories' => [$category2],
                'categoryOperator' => 'or',
                'websiteCategories' => [$category1],
                'websiteCategoriesOperator' => 'or',
                'tags' => [$tag2],
                'tagOperator' => 'or',
                'websiteTags' => [$tag1],
                'websiteTagsOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$account1, $account5], $result);
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

    private function createTag($name)
    {
        $tag = $this->getContainer()->get('sulu.repository.tag')->createNew();
        $tag->setName($name);

        $this->em->persist($tag);

        return $tag;
    }

    private function createCategory($key)
    {
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey($key);
        $category->setDefaultLocale('en');

        $this->em->persist($category);

        return $category;
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
