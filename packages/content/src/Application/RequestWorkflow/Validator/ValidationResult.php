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

namespace Sulu\Content\Application\RequestWorkflow\Validator;

use Sulu\Content\Domain\Model\WorkflowTransitionRequest\DecisionMessage;

/**
 * What a validator or pre-validator answers. Messages travel on a pass too, so the admin can list what
 * a check looked at as well as what it objected to.
 */
final class ValidationResult
{
    /**
     * @param list<DecisionMessage> $messages
     */
    private function __construct(
        public readonly bool $approved,
        public readonly array $messages,
    ) {
    }

    public static function approve(DecisionMessage ...$messages): self
    {
        return new self(true, \array_values($messages));
    }

    public static function reject(DecisionMessage ...$messages): self
    {
        if ([] === $messages) {
            throw new \InvalidArgumentException('A rejecting validator must say what is wrong.');
        }

        return new self(false, \array_values($messages));
    }

    /**
     * @return list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>
     */
    public function messagesToArray(): array
    {
        return \array_map(static fn (DecisionMessage $message) => $message->toArray(), $this->messages);
    }
}
