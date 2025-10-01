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

namespace Sulu\Content\Domain\Model;

interface ContentBehaviorInterface
{
    public const BEHAVIOR_CONTENT = 'content';
    public const BEHAVIOR_INTERNAL = 'internal';
    public const BEHAVIOR_EXTERNAL = 'external';

    public function getBehavior(): string;

    public function setBehavior(string $behavior): void;

    /**
     * @param string|null $contentBehavior If provided, returns only data for that specific behavior (e.g., $data['internal'])
     *
     * @return array<string, mixed>|null
     */
    public function getBehaviorData(?string $contentBehavior = null): ?array;

    /**
     * @param array<string, mixed>|null $data
     */
    public function setBehaviorData(?array $data): void;
}
