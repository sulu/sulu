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

class ModifySnippetAreaMessage
{
    private string $webspace;
    private string $areaKey;

    /**
     * @var array{ uuid: string }
     */
    private array $snippetIdentifier;

    /**
     * @param array{webspaceKey: string, key: string, snippetIdentifier: array{uuid: string}} $data
     */
    public function __construct(array $data)
    {
        $this->webspace = $data['webspaceKey'];
        $this->areaKey = $data['key'];
        $this->snippetIdentifier = $data['snippetIdentifier'];
    }

    public function getWebspace(): string
    {
        return $this->webspace;
    }

    public function getAreaKey(): string
    {
        return $this->areaKey;
    }

    /**
     * @return array{ uuid: string }
     */
    public function getSnippetIdentifier(): array
    {
        return $this->snippetIdentifier;
    }
}
