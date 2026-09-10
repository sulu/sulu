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

namespace Sulu\Content\Domain\Model\WorkflowTransitionRequest;

/**
 * One thing said about a request. A translated message carries a key the admin resolves, raw text is
 * for what no fixed key can express, like the list of ids a check found.
 */
final class DecisionMessage
{
    /**
     * @param array<string, float|int|string> $parameters
     */
    private function __construct(
        public readonly ?string $key,
        public readonly array $parameters,
        public readonly ?string $text,
    ) {
    }

    /**
     * @param array<string, float|int|string> $parameters
     */
    public static function translated(string $key, array $parameters = []): self
    {
        return new self($key, $parameters, null);
    }

    public static function text(string $text): self
    {
        if ('' === \trim($text)) {
            throw new \InvalidArgumentException('A message must not be empty.');
        }

        return new self(null, [], $text);
    }

    /**
     * @param array{key: string|null, parameters: array<string, float|int|string>, text: string|null} $message
     */
    public static function fromArray(array $message): self
    {
        return new self($message['key'], $message['parameters'], $message['text']);
    }

    /**
     * @return array{key: string|null, parameters: array<string, float|int|string>, text: string|null}
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'parameters' => $this->parameters, 'text' => $this->text];
    }
}
