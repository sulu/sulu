<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;

/**
 * @extends EntityRepository<PreviewLinkInterface>
 */
class PreviewLinkRepository extends EntityRepository implements PreviewLinkRepositoryInterface
{
    public function createNew(): PreviewLinkInterface
    {
        /** @var class-string<PreviewLinkInterface> $className */
        $className = $this->getClassName();

        return new $className();
    }

    public function findByToken(string $token): ?PreviewLinkInterface
    {
        /** @var PreviewLinkInterface|null $previewLink */
        $previewLink = $this->findOneBy(['token' => $token]);

        return $previewLink;
    }

    public function add(PreviewLinkInterface $previewLink): void
    {
        $this->getEntityManager()->persist($previewLink);
    }

    public function commit(): void
    {
        $this->getEntityManager()->flush();
    }
}
