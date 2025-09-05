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
