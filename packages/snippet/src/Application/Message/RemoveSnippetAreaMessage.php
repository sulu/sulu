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
    private string $webspaceKey;
    private string $areaKey;

    /**
     * @param array{webspaceKey: string, areaKey: string} $data
     */
    public function __construct(array $data)
    {
        $this->webspaceKey = $data['webspaceKey'];
        $this->areaKey = $data['areaKey'];
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
