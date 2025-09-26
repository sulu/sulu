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

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;

/**
 * @phpstan-type Contact array{
 *     id: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     firstName: string,
 *     lastName: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class ContactReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<ContactInterface>
     */
    protected EntityRepository $contactRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $repository = $entityManager->getRepository(ContactInterface::class);

        $this->contactRepository = $repository;
    }

    public function total(): int
    {
        return $this->contactRepository->count([]);
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $contacts = $this->loadContacts($reindexConfig->getIdentifiers());

        /** @var Contact $contact */
        foreach ($contacts as $contact) {
            yield [
                'id' => ContactInterface::RESOURCE_KEY . '::' . ((string) $contact['id']),
                'resourceKey' => ContactInterface::RESOURCE_KEY,
                'resourceId' => (string) $contact['id'],
                'changedAt' => $contact['changed']->format('c'),
                'createdAt' => $contact['created']->format('c'),
                'title' => $contact['firstName'] . ' ' . $contact['lastName'],
            ];
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Contact>
     */
    private function loadContacts(array $identifiers = []): iterable
    {
        $qb = $this->contactRepository->createQueryBuilder('contact')
            ->select('contact.id')
            ->addSelect('contact.firstName')
            ->addSelect('contact.lastName')
            ->addSelect('contact.created')
            ->addSelect('contact.changed');

        if (0 < \count($identifiers)) {
            $qb->where('contact.id IN (:ids)')
                ->setParameter('ids', \array_map(fn ($identifier) => (int) \str_replace(ContactInterface::RESOURCE_KEY . '::', '', $identifier), $identifiers));
        }

        /** @var iterable<Contact> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
