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

trait ContentBehaviorTrait
{
    private string $behavior = ContentBehaviorInterface::BEHAVIOR_CONTENT;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $behaviorData = null;

    public function getBehavior(): string
    {
        return $this->behavior;
    }

    public function setBehavior(string $behavior): void
    {
        $this->behavior = $behavior;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBehaviorData(?string $contentBehavior = null): ?array
    {
        if (null !== $contentBehavior) {
            /** @var array<string, mixed>|null */
            return $this->behaviorData[$contentBehavior] ?? null;
        }

        return $this->behaviorData;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function setBehaviorData(?array $data): void
    {
        $this->behaviorData = $data;
    }
}
