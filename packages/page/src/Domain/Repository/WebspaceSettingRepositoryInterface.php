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

namespace Sulu\Page\Domain\Repository;

use Sulu\Page\Domain\Exception\WebspaceSettingNotFoundException;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;

/**
 * @phpstan-type WebspaceSettingFilters array{
 *     webspaceKey?: string,
 *     locale?: string|null,
 *     stage?: string|null,
 *     version?: int,
 *     loadGhost?: bool,
 * }
 * @phpstan-type WebspaceSettingSelects array{
 *     with-webspace-setting-content?: bool|array<string, mixed>,
 * }|array<string, mixed>
 *
 * @see \Sulu\Page\Infrastructure\Doctrine\Repository\WebspaceSettingRepository
 */
interface WebspaceSettingRepositoryInterface
{
    public const SELECT_WEBSPACE_SETTING_CONTENT = 'with-webspace-setting-content';

    public function createNew(string $webspaceKey): WebspaceSettingInterface;

    /**
     * @param WebspaceSettingFilters $filters
     * @param WebspaceSettingSelects $selects
     *
     * @throws WebspaceSettingNotFoundException
     */
    public function getOneBy(array $filters, array $selects = []): WebspaceSettingInterface;

    /**
     * @param WebspaceSettingFilters $filters
     * @param WebspaceSettingSelects $selects
     */
    public function findOneBy(array $filters, array $selects = []): ?WebspaceSettingInterface;

    /**
     * Returns the stored settings of the webspace or new ones, which are not persisted, if it has none yet.
     *
     * @param WebspaceSettingFilters $filters
     * @param WebspaceSettingSelects $selects
     */
    public function getOrNew(string $webspaceKey, array $filters = [], array $selects = []): WebspaceSettingInterface;

    public function add(WebspaceSettingInterface $webspaceSetting): void;
}
