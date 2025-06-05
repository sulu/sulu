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

namespace Sulu\Article\Domain\Model;

use Sulu\Content\Domain\Model\WebspaceInterface;

interface AdditionalWebspacesInterface extends WebspaceInterface
{
    public function getCustomizeWebspaceSettings(): bool;

    public function setCustomizeWebspaceSettings(bool $customizeWebspaceSettings): void;

    /**
     * @return string[]|null
     */
    public function getAdditionalWebspaces(): ?array;

    /**
     * @param string[]|null $additionalWebspaces
     */
    public function setAdditionalWebspaces(?array $additionalWebspaces): void;

    public function addAdditionalWebspace(string $webspace): void;

    public function removeAdditionalWebspace(string $webspace): void;
}