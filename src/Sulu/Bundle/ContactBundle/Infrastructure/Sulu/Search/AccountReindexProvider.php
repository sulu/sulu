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
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

/**
 * @phpstan-type Account array{
 *     id: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     name: string
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AccountReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<AccountInterface>
     */
    protected EntityRepository $accountRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $repository = $entityManager->getRepository(AccountInterface::class);

        $this->accountRepository = $repository;
    }

    public function total(): int
    {
        return $this->accountRepository->count([]);
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $accounts = $this->loadAccounts($reindexConfig->getIdentifiers());

        /** @var Account $account */
        foreach ($accounts as $account) {
            yield [
                'id' => AccountInterface::RESOURCE_KEY . '::' . ((string) $account['id']),
                'resourceKey' => AccountInterface::RESOURCE_KEY,
                'resourceId' => (string) $account['id'],
                'changedAt' => $account['changed']->format('c'),
                'createdAt' => $account['created']->format('c'),
                'title' => $account['name'],
            ];
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Account>
     */
    private function loadAccounts(array $identifiers = []): iterable
    {
        $qb = $this->accountRepository->createQueryBuilder('account')
            ->select('account.id')
            ->addSelect('account.name')
            ->addSelect('account.created')
            ->addSelect('account.changed');

        if (0 < \count($identifiers)) {
            $qb->where('account.id IN (:ids)')
                ->setParameter('ids', \array_map(fn ($identifier) => (int) \str_replace(AccountInterface::RESOURCE_KEY . '::', '', $identifier), $identifiers));
        }

        /** @var iterable<Account> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
