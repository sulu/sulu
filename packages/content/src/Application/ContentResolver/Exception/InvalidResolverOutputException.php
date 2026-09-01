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

namespace Sulu\Content\Application\ContentResolver\Exception;

/**
 * @internal This exception is intended for internal use only within the package/library.
 * Modifying or depending on this exception may result in unexpected behavior and is not supported.
 */
final class InvalidResolverOutputException extends \LogicException
{
    private string $type;

    /**
     * @param list<string> $segments
     */
    public function __construct(string $type, array $segments, string $reason)
    {
        $this->type = $type;

        parent::__construct(\sprintf(
            'Content resolver "%s" cannot be merged into "[root]%s": %s.',
            $type,
            \implode('', \array_map(static fn (string $segment) => '[' . $segment . ']', $segments)),
            $reason,
        ));
    }

    public function getType(): string
    {
        return $this->type;
    }
}
