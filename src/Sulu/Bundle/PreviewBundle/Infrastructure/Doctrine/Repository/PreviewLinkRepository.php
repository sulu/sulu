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
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;

/**
 * @extends EntityRepository<PreviewLinkInterface>
 */
class PreviewLinkRepository extends EntityRepository implements PreviewLinkRepositoryInterface
{
    public function createNew(string $resourceKey, string $resourceId, string $locale, array $options): PreviewLinkInterface
    {
        $token = $this->generateToken();

        /** @var class-string<PreviewLinkInterface> $className */
        $className = $this->getClassName();

        return new $className($token, $resourceKey, $resourceId, $locale, $options);
    }

    public function findByResource(string $resourceKey, string $resourceId, string $locale): ?PreviewLinkInterface
    {
        /** @var PreviewLinkInterface|null $previewLink */
        $previewLink = $this->findOneBy(['resourceKey' => $resourceKey, 'resourceId' => $resourceId, 'locale' => $locale]);

        return $previewLink;
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

    public function remove(PreviewLinkInterface $previewLink): void
    {
        $this->getEntityManager()->remove($previewLink);
    }

    public function commit(): void
    {
        $this->getEntityManager()->flush();
    }

    protected function generateToken(): string
    {
        $token = \substr(\md5(Uuid::uuid4()->toString()), 0, 12);
        if ($this->findByToken($token)) {
            return $this->generateToken();
        }

        return $token;
    }
}
