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

namespace Sulu\Snippet\Application\Message;

class RemoveSnippetAreaMessage
{
    public function __construct(
        private string $webspaceKey,
        private string $areaKey,
    ) {
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getAreaKey(): string
    {
        return $this->areaKey;
    }
}
