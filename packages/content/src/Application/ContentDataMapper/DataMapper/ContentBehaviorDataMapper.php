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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Content\Domain\Model\ContentBehaviorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own data mapper instead.
 */
class ContentBehaviorDataMapper implements DataMapperInterface
{
    private const VALID_BEHAVIORS = [
        ContentBehaviorInterface::BEHAVIOR_CONTENT,
        ContentBehaviorInterface::BEHAVIOR_INTERNAL,
        ContentBehaviorInterface::BEHAVIOR_EXTERNAL,
    ];

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof ContentBehaviorInterface) {
            return;
        }

        $behavior = null;
        if (\array_key_exists('behavior', $data) && \is_string($data['behavior'])) {
            if (\in_array($data['behavior'], self::VALID_BEHAVIORS, true)) {
                $behavior = $data['behavior'];
                $localizedDimensionContent->setBehavior($behavior);
            }
        }

        if (null === $behavior) {
            return;
        }

        $behaviorData = null;
        foreach (self::VALID_BEHAVIORS as $behaviorValue) {
            $key = 'behaviorData' . \ucfirst($behaviorValue);
            if (\array_key_exists($key, $data)) {
                $behaviorData[$behaviorValue] = $data[$key];
            }
        }

        /** @var array<string, mixed>|null $finalBehaviorData */
        $finalBehaviorData = $behaviorData ?? $data['behaviorData'] ?? null;
        $localizedDimensionContent->setBehaviorData($finalBehaviorData);
    }
}
