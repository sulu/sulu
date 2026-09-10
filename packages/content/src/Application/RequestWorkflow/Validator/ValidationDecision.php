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

final class ValidationDecision
{
    private function __construct(
        public readonly bool $approved,
        public readonly ?string $comment,
    ) {
    }

    public static function approve(?string $comment = null): self
    {
        return new self(true, $comment);
    }

    public static function reject(string $comment): self
    {
        if ('' === \trim($comment)) {
            throw new \InvalidArgumentException('A rejecting validator must say what is wrong.');
        }

        return new self(false, $comment);
    }
}
