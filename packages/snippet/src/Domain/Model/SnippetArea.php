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

namespace Sulu\Snippet\Domain\Model;

use Symfony\Component\Uid\Uuid;

class SnippetArea implements SnippetAreaInterface
{
    protected string $uuid;

    private string $webspaceKey;

    private string $areaKey;

    private ?SnippetInterface $snippet = null;

    public function __construct(
        string $areaKey,
        string $webspaceKey,
        ?string $uuid = null,
    ) {
        $this->uuid = $uuid ?: Uuid::v7()->__toString();

        $this->areaKey = $areaKey;
        $this->webspaceKey = $webspaceKey;
    }

    /**
     * @deprecated use getUuid
     */
    public function getId(): string
    {
        return $this->uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setWebspaceKey(string $webspaceKey): void
    {
        $this->webspaceKey = $webspaceKey;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function setAreaKey(string $areaKey): void
    {
        $this->areaKey = $areaKey;
    }

    public function getAreaKey(): string
    {
        return $this->areaKey;
    }

    public function setSnippet(?SnippetInterface $snippet): void
    {
        $this->snippet = $snippet;
    }

    public function getSnippet(): ?SnippetInterface
    {
        return $this->snippet;
    }
}
