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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContactRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ContactRepository
     */
    private $contactRepository;

    public function setUp(): void
    {
        $this->em = $this->getEntityManager();
        $this->contactRepository = $this->em->getRepository(Contact::class);
        $this->purgeDatabase();
    }

    public function testFindByNoPagination(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Erika', 'Mustermann');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters([], null, 0, null, 'de');

        $this->assertEquals([$contact1, $contact2], $result);
    }

    public function testFindByPage1NoLimit(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters([], 1, 2, null, 'de');

        // One more element is returned in order to determine if next page is available
        $this->assertEquals([$contact1, $contact2, $contact3], $result);
    }

    public function testFindByPage2NoLimit(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters([], 2, 2, null, 'de');

        $this->assertEquals([$contact3, $contact4], $result);
    }

    public function testFindByLimit3(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters([], null, 0, 3, 'de');

        $this->assertEquals([$contact1, $contact2, $contact3], $result);
    }

    public function testFindByPage1Limit5(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau');
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters([], 1, 3, 5, 'de');

        $this->assertEquals([$contact1, $contact2, $contact3, $contact4], $result);
    }

    public function testFindByPage2Limit5(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau');
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters([], 2, 3, 5, 'de');

        $this->assertEquals([$contact4, $contact5], $result);
    }

    public function testFindByLimit2OrderAsc(): void
    {
        $contact1 = $this->createContact('AA', 'Mustermann');
        $contact2 = $this->createContact('BB', 'Mustermann');
        $contact3 = $this->createContact('XX', 'Musterfrau');
        $contact4 = $this->createContact('ZZ', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(['sortBy' => 'firstName', 'sortMethod' => 'asc'], 0, 0, 2, 'de');

        $this->assertEquals([$contact1, $contact2], $result);
    }

    public function testFindByLimit2OrderDesc(): void
    {
        $contact1 = $this->createContact('AA', 'Mustermann');
        $contact2 = $this->createContact('BB', 'Mustermann');
        $contact3 = $this->createContact('XX', 'Musterfrau');
        $contact4 = $this->createContact('ZZ', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(['sortBy' => 'firstName', 'sortMethod' => 'desc'], 0, 0, 2, 'de');

        $this->assertEquals([$contact4, $contact3], $result);
    }

    public function testFindByTagOr(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [$tag1]);
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag2]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['tags' => [$tag1->getId(), $tag2->getId()], 'tagOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByTagAnd(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [$tag1, $tag2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [$tag1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [$tag2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag2, $tag1]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['tags' => [$tag1->getId(), $tag2->getId()], 'tagOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByWebsiteTagOr(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [$tag1]);
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag2]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['websiteTags' => [$tag1->getId(), $tag2->getId()], 'websiteTagsOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByWebsiteTagAnd(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [$tag1, $tag2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [$tag1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [$tag2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag2, $tag1]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['websiteTags' => [$tag1->getId(), $tag2->getId()], 'websiteTagsOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByTagAndWebsiteTag(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [$tag1, $tag2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [$tag1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [$tag2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag2, $tag1]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            [
                'websiteTags' => [$tag1],
                'websiteTagsOperator' => 'and',
                'tags' => [$tag2],
                'tagOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByTagAndWebsiteTagOr(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $tag3 = $this->createTag('Tag 3');
        $contact1 = $this->createContact('Max', 'Mustermann', [$tag1, $tag2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [$tag1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [$tag2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag2, $tag3]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau', [$tag1, $tag3]);

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            [
                'websiteTags' => [$tag1, $tag3],
                'websiteTagsOperator' => 'or',
                'tags' => [$tag2],
                'tagOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByTagOrAndWebsiteTagOr(): void
    {
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $tag3 = $this->createTag('Tag 3');
        $contact1 = $this->createContact('Max', 'Mustermann', [$tag1, $tag2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [$tag1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [$tag2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag2, $tag3]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau', [$tag1, $tag3]);

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            [
                'websiteTags' => [$tag2],
                'websiteTagsOperator' => 'or',
                'tags' => [$tag1, $tag3],
                'tagOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByCategoryOr(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [], [$category1]);
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau', [], [$category2]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['categories' => [$category1->getId(), $category2->getId()], 'categoryOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByCategoryAnd(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [], [$category1, $category2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [], [$category1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [], [$category2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [], [$category2, $category1]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['categories' => [$category1->getId(), $category2->getId()], 'categoryOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByWebsiteCategoryOr(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [], [$category1]);
        $contact2 = $this->createContact('Erika', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Anne', 'Musterfrau', [], [$category2]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['websiteCategories' => [$category1->getId(), $category2->getId()], 'websiteCategoriesOperator' => 'or'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByWebsiteCategoryAnd(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [], [$category1, $category2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [], [$category1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [], [$category2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [], [$category2, $category1]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            ['websiteCategories' => [$category1->getId(), $category2->getId()], 'websiteCategoriesOperator' => 'and'],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByCategoryAndWebsiteCategory(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [], [$category1, $category2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [], [$category1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [], [$category2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [], [$category2, $category1]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau');

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            [
                'websiteCategories' => [$category1],
                'websiteCategoriesOperator' => 'and',
                'categories' => [$category2],
                'categoryOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByCategoryAndWebsiteCategoryOr(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $category3 = $this->createCategory('Category 3');
        $contact1 = $this->createContact('Max', 'Mustermann', [], [$category1, $category2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [], [$category1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [], [$category2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [], [$category2, $category3]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau', [], [$category1, $category3]);

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            [
                'websiteCategories' => [$category1, $category3],
                'websiteCategoriesOperator' => 'or',
                'categories' => [$category2],
                'categoryOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByCategoryOrAndWebsiteCategoryOr(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $category3 = $this->createCategory('Category 3');
        $contact1 = $this->createContact('Max', 'Mustermann', [], [$category1, $category2]);
        $contact2 = $this->createContact('Erika', 'Mustermann', [], [$category1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [], [$category2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [], [$category2, $category3]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau', [], [$category1, $category3]);

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            [
                'websiteCategories' => [$category2],
                'websiteCategoriesOperator' => 'or',
                'categories' => [$category1, $category3],
                'categoryOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact1, $contact4], $result);
    }

    public function testFindByCategoryAndTag(): void
    {
        $category1 = $this->createCategory('Category 1');
        $tag1 = $this->createTag('Tag 1');
        $contact1 = $this->createContact('Max', 'Mustermann', [], []);
        $contact2 = $this->createContact('Erika', 'Mustermann', [], [$category1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [$tag1], []);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag1], [$category1]);
        $contact5 = $this->createContact('Gustav', 'Musterfrau', [$tag1], [$category1]);

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
            [
                'categories' => [$category1],
                'categoryOperator' => 'or',
                'tags' => [$tag1],
                'tagOperator' => 'or',
            ],
            null,
            0,
            null,
            'de'
        );

        $this->assertEquals([$contact4, $contact5], $result);
    }

    public function testFindByWebsiteCategoryAndWebsiteTag(): void
    {
        $category1 = $this->createCategory('Category 1');
        $category2 = $this->createCategory('Category 2');
        $tag1 = $this->createTag('Tag 1');
        $tag2 = $this->createTag('Tag 2');
        $contact1 = $this->createContact('Max', 'Mustermann', [], []);
        $contact2 = $this->createContact('Erika', 'Mustermann', [$tag1], [$category1]);
        $contact3 = $this->createContact('Georg', 'Mustermann', [$tag2], [$category2]);
        $contact4 = $this->createContact('Anne', 'Musterfrau', [$tag1], []);
        $contact5 = $this->createContact('Gustav', 'Musterfrau', [], [$category1]);
        $contact6 = $this->createContact('Leonard', 'Musterfrau', [$tag1, $tag2], [$category1, $category2]);

        $this->em->flush();

        $result = $this->contactRepository->findByFilters(
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

        $this->assertEquals([$contact6], $result);
    }

    public function testFindByIds(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Anne', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $this->em->flush();

        $result = $this->contactRepository->findByIds([$contact1->getId(), $contact2->getId()]);

        $this->assertCount(2, $result);
        $this->assertEquals('Max', $result[0]->getFirstName());
        $this->assertEquals('Anne', $result[1]->getFirstName());
    }

    public function testFindByNotExistingIds(): void
    {
        $result = $this->contactRepository->findByIds([15, 99]);

        $this->assertCount(0, $result);
    }

    public function testFindByIdsEmpty(): void
    {
        $result = $this->contactRepository->findByIds([]);

        $this->assertCount(0, $result);
    }

    public function testFindGetAllSortByIdAsc(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Anne', 'Mustermann');
        $this->em->flush();

        $result = $this->contactRepository->findGetAll(null, null, ['id' => 'asc'], []);

        $this->assertEquals('Max', $result[0]['firstName']);
        $this->assertEquals('Anne', $result[1]['firstName']);
    }

    public function testFindGetAllSortByFirstNameAsc(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Anne', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $this->em->flush();

        $result = $this->contactRepository->findGetAll(null, null, ['firstName' => 'asc'], []);

        $this->assertEquals('Anne', $result[0]['firstName']);
        $this->assertEquals('Georg', $result[1]['firstName']);
        $this->assertEquals('Max', $result[2]['firstName']);
    }

    public function testFindGetAllSortByFirstNameDesc(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Anne', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $this->em->flush();

        $result = $this->contactRepository->findGetAll(null, null, ['firstName' => 'desc'], []);

        $this->assertEquals('Max', $result[0]['firstName']);
        $this->assertEquals('Georg', $result[1]['firstName']);
        $this->assertEquals('Anne', $result[2]['firstName']);
    }

    public function testFindGetAllSortByIdAscWithLimit(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Anne', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Erika', 'Mustermann');
        $this->em->flush();

        $result = $this->contactRepository->findGetAll(3, null, ['id' => 'asc'], []);

        $this->assertCount(3, $result);
        $this->assertEquals('Max', $result[0]['firstName']);
        $this->assertEquals('Anne', $result[1]['firstName']);
        $this->assertEquals('Georg', $result[2]['firstName']);
    }

    public function testFindGetAllSortByIdAscWithLimitAndOffset(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Anne', 'Mustermann');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Erika', 'Mustermann');
        $this->em->flush();

        $result = $this->contactRepository->findGetAll(3, 1, ['id' => 'asc'], []);

        $this->assertCount(3, $result);
        $this->assertEquals('Anne', $result[0]['firstName']);
        $this->assertEquals('Georg', $result[1]['firstName']);
        $this->assertEquals('Erika', $result[2]['firstName']);
    }

    public function testFindGetAllSortByIdWithLastName(): void
    {
        $contact1 = $this->createContact('Max', 'Mustermann');
        $contact2 = $this->createContact('Anne', 'Musterfrau');
        $contact3 = $this->createContact('Georg', 'Mustermann');
        $contact4 = $this->createContact('Erika', 'Musterfrau');
        $this->em->flush();

        $result = $this->contactRepository->findGetAll(1, null, ['id' => 'asc'], ['lastName' => 'Musterfrau']);

        $this->assertCount(1, $result);
        $this->assertEquals('Anne', $result[0]['firstName']);
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

    private function createContact($firstName, $lastName, $tags = [], $categories = [])
    {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setFormOfAddress(0);

        foreach ($tags as $tag) {
            $contact->addTag($tag);
        }

        foreach ($categories as $category) {
            $contact->addCategory($category);
        }

        $this->em->persist($contact);

        return $contact;
    }
}
