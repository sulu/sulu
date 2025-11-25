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

interface SnippetAreaInterface
{
    public const RESOURCE_KEY = 'snippet_areas';

    public function setWebspaceKey(string $webspaceKey): static;

    public function getWebspaceKey(): string;

    public function setAreaKey(string $areaKey): static;

    public function getAreaKey(): string;

    public function setSnippet(?SnippetInterface $snippet): static;

    public function getSnippet(): ?SnippetInterface;

    public function getUuid(): string;
}
