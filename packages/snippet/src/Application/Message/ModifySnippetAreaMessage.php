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
     * @var array{ uuid: string } */
    private array $snippet;

    /**
     * @param array<string,string> $requestData
     */
    public function __construct(array $requestData)
    {
        $this->webspace = $requestData['webspace'];
        $this->areaKey = $requestData['key'];
        $this->snippet = $requestData['snippet'];
    }

    public function getWebspace(): string
    {
        return $this->webspace;
    }

    public function getAreaKey(): string
    {
        return $this->areaKey;
    }

    public function getSnippet(): array
    {
        return $this->snippet;
    }
}
