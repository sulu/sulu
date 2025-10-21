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

/** @phpstan-type ModifySnippetAreaMessageData array{
 *     webspaceKey: string,
 *     areaKey: string,
 *     snippetIdentifier: array{uuid: string},
 *     locale: string,
 *  }
 */
class ModifySnippetAreaMessage
{
    private string $webspaceKey;
    private string $areaKey;
    private string $locale;
    /** @var array{ uuid: string } */
    private array $snippetIdentifier;

    /**
     * @param ModifySnippetAreaMessageData $data
     */
    public function __construct(array $data)
    {
        $this->webspaceKey = $data['webspaceKey'];
        $this->areaKey = $data['areaKey'];
        $this->snippetIdentifier = $data['snippetIdentifier'];
        $this->locale = $data['locale'];
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    /** @return ModifySnippetAreaMessageData */
    public function getData(): array
    {
        return [
            'webspaceKey' => $this->webspaceKey,
            'snippetIdentifier' => $this->snippetIdentifier,
            'areaKey' => $this->areaKey,
            'locale' => $this->locale,
        ];
    }
}
