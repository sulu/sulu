<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Entity;

use Sulu\Component\Persistence\Repository\RepositoryInterface;

interface AnalyticsRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns list of analytics filtered by webspace key and environment.
     *
     * @return AnalyticsInterface[]
     */
    public function findByWebspaceKey(string $webspaceKey, string $environment): array;

    public function findById(int $id): AnalyticsInterface;

    /**
     * @return AnalyticsInterface[]
     */
    public function findByUrl(string $url, string $webspaceKey, string $environment): array;
}
