<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\ContactReindexProvider;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContactReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;

    private EntityManagerInterface $entityManager;
    private ContactReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new ContactReindexProvider($this->entityManager);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', ContactReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->createContact();
        $this->createContact();

        $this->entityManager->flush();

        $this->assertSame(2, $this->provider->total());
    }

    public function testProvideAll(): void
    {
        $contact1 = $this->createContact();
        $contact2 = $this->createContact();

        $this->entityManager->flush();

        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE co_contacts SET changed = :changed WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'id' => $contact1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'id' => $contact2->getId(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $this->assertSame(
            [
                [
                    'id' => ContactInterface::RESOURCE_KEY . '::' . $contact1->getId(),
                    'resourceKey' => ContactInterface::RESOURCE_KEY,
                    'resourceId' => (string) $contact1->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $contact1->getFullName(),
                ],
                [
                    'id' => ContactInterface::RESOURCE_KEY . '::' . $contact2->getId(),
                    'resourceKey' => ContactInterface::RESOURCE_KEY,
                    'resourceId' => (string) $contact2->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $contact2->getFullName(),
                ],
            ],
            [...$results],
        );
    }

    public function testProvideWithSpecificIdentifiers(): void
    {
        $contact1 = $this->createContact();
        $contact2 = $this->createContact('Fritz', 'Fantom');
        $contact3 = $this->createContact('Thomas', 'Brezina');

        $this->entityManager->flush();

        $identifiers = [
            ContactInterface::RESOURCE_KEY . '::' . $contact1->getId(),
            ContactInterface::RESOURCE_KEY . '::' . $contact3->getId(),
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Tom Turbo', $resultTitles);
        $this->assertContains('Thomas Brezina', $resultTitles);
        $this->assertNotContains('Fritz Fantom', $resultTitles);
    }

    private function createContact(string $firstName = 'Tom', string $lastName = 'Turbo'): Contact
    {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setCreated(new \DateTimeImmutable('2000-01-01 12:00:00'));

        $this->entityManager->persist($contact);

        return $contact;
    }
}
