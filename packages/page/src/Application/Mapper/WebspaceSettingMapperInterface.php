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

namespace Sulu\Page\Application\Mapper;

use Sulu\Page\Domain\Model\WebspaceSettingInterface;

interface WebspaceSettingMapperInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function mapWebspaceSettingData(WebspaceSettingInterface $webspaceSetting, array $data): void;
}
