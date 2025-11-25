<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\Message;

/**
 * @internal This class should not be instantiated by a project.
 *            Create your own Message and Handler instead.
 */
final class RemoveRouteHistoryMessage
{
    public function __construct(
        /** @var array{ id?: int } $identifier */
        private array $identifier,
    ) {
    }

    /**
     * @return array{
     *     id?: int
     * }
     */
    public function getIdentifier(): array
    {
        return $this->identifier;
    }
}
