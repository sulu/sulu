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

namespace Sulu\Page\Domain\Exception;

use Sulu\Page\Domain\Model\WebspaceSettingInterface;

class WebspaceSettingNotFoundException extends \Exception
{
    public function __construct(string $webspaceKey, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('Model "%s" with "webspaceKey" "%s" not found', WebspaceSettingInterface::class, $webspaceKey),
            $code,
            $previous,
        );
    }
}
