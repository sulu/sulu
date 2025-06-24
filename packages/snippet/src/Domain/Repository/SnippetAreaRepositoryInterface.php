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

namespace Sulu\Snippet\Domain\Repository;

use Sulu\Snippet\Domain\Model\SnippetAreaInterface;

/**
 * Implementation can be found in the following class:.
 *
 * @see Sulu\Snippet\Infrastructure\Doctrine\Repository\SnippetAreaRepository
 */
interface SnippetAreaRepositoryInterface
{
    public function createNew(string $areaKey, string $webspaceKey, ?string $uuid): SnippetAreaInterface;

    public function findOneByWebspaceAndKey(string $webspaceKey, string $areaKey): ?SnippetAreaInterface;
}
