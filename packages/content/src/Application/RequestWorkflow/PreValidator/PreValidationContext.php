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

namespace Sulu\Content\Application\RequestWorkflow\PreValidator;

use Sulu\Content\Domain\Model\DimensionContentInterface;

final class PreValidationContext
{
    /**
     * @template T of \Sulu\Content\Domain\Model\ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     * @param array<string, mixed> $config the resolved per-workflow config block for this pre-validator
     */
    public function __construct(
        public readonly DimensionContentInterface $dimensionContent,
        public readonly array $config,
        public readonly string $workflowName,
    ) {
    }
}
