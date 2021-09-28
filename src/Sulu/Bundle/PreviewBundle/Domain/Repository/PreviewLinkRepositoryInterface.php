<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Domain\Repository;

use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;

interface PreviewLinkRepositoryInterface
{
    /**
     * @param mixed[] $options
     */
    public function createNew(string $resourceKey, string $resourceId, string $locale, array $options): PreviewLinkInterface;

    public function findByResource(string $resourceKey, string $resourceId, string $locale): ?PreviewLinkInterface;

    public function findByToken(string $token): ?PreviewLinkInterface;

    public function add(PreviewLinkInterface $previewLink): void;

    public function remove(PreviewLinkInterface $previewLink): void;

    public function commit(): void;
}
