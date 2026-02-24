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
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\Visitor\AdminContactReindexProviderEnhancerInterface;

/**
 * @phpstan-type Contact array{
 *     id: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     firstName: string,
 *     lastName: string,
 *     mediaId: int|null
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminContactReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<ContactInterface>
     */
    private EntityRepository $contactRepository;

    /**
     * @param iterable<AdminContactReindexProviderEnhancerInterface> $enhancers
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly iterable $enhancers = [],
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
            $data = [
                'id' => ContactInterface::RESOURCE_KEY . '__' . ((string) $contact['id']),
                'resourceKey' => ContactInterface::RESOURCE_KEY,
                'resourceId' => (string) $contact['id'],
                'mediaId' => (string) $contact['mediaId'],
                'changedAt' => $contact['changed']->format('c'),
                'createdAt' => $contact['created']->format('c'),
                'title' => $contact['firstName'] . ' ' . $contact['lastName'],
                'securityContext' => ContactAdmin::CONTACT_SECURITY_CONTEXT,
            ];

            foreach ($this->enhancers as $enhancer) {
                $data = $enhancer->enhanceDocument($contact, $data);
            }

            yield $data;
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
            ->addSelect('IDENTITY(contact.avatar) as mediaId')
            ->addSelect('contact.created')
            ->addSelect('contact.changed');

        if (0 < \count($identifiers)) {
            $qb->where('contact.id IN (:ids)')
                ->setParameter('ids', \array_map(fn ($identifier) => (int) \str_replace(ContactInterface::RESOURCE_KEY . '__', '', $identifier), $identifiers));
        }

        foreach ($this->enhancers as $enhancer) {
            $enhancer->enhanceQuery($qb);
        }

        /** @var iterable<Contact> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
