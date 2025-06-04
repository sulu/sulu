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

namespace Sulu\Snippet\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;

class SnippetAreaRepository implements SnippetAreaRepositoryInterface
{
    /**
     * @var EntityRepository<SnippetAreaInterface>
     */
    private EntityRepository $entityRepository;

    private string $snippetAreaClassName;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $this->entityRepository = $entityManager->getRepository(SnippetAreaInterface::class);
        $this->snippetAreaClassName = $this->entityRepository->getClassName();
    }

    public function createNew(?string $uuid = null, string $areaKey, string $webspaceKey): SnippetAreaInterface
    {
        $className = $this->snippetAreaClassName;

        return new $className($uuid, $areaKey, $webspaceKey);
    }

    public function findOneByWebspaceAndKey(string $webspaceKey, string $areaKey): ?SnippetInterface
    {
        return $this->entityRepository->findOneBy(['webspaceKey' => $webspaceKey, 'areaKey' => $areaKey]);
    }
}
