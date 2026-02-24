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
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\Visitor\AdminAccountReindexProviderEnhancerInterface;

/**
 * @phpstan-type Account array{
 *     id: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     name: string,
 *     mediaId: int|null,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminAccountReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<AccountInterface>
     */
    private EntityRepository $accountRepository;

    /**
     * @param iterable<AdminAccountReindexProviderEnhancerInterface> $enhancers
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly iterable $enhancers = [],
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
            $data = [
                'id' => AccountInterface::RESOURCE_KEY . '__' . ((string) $account['id']),
                'resourceKey' => AccountInterface::RESOURCE_KEY,
                'resourceId' => (string) $account['id'],
                'mediaId' => (string) $account['mediaId'],
                'changedAt' => $account['changed']->format('c'),
                'createdAt' => $account['created']->format('c'),
                'title' => $account['name'],
                'securityContext' => ContactAdmin::ACCOUNT_SECURITY_CONTEXT,
            ];

            foreach ($this->enhancers as $enhancer) {
                $data = $enhancer->enhanceDocument($account, $data);
            }

            yield $data;
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
            ->addSelect('IDENTITY(account.logo) as mediaId')
            ->addSelect('account.created')
            ->addSelect('account.changed');

        if (0 < \count($identifiers)) {
            $qb->where('account.id IN (:ids)')
                ->setParameter('ids', \array_map(fn ($identifier) => (int) \str_replace(AccountInterface::RESOURCE_KEY . '__', '', $identifier), $identifiers));
        }

        foreach ($this->enhancers as $enhancer) {
            $enhancer->enhanceQuery($qb);
        }

        /** @var iterable<Account> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
