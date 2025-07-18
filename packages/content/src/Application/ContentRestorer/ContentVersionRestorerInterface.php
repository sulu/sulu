<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentRestorer;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;

/**
 * @template T of ContentRichEntityInterface
 */
interface ContentVersionRestorerInterface
{
    /**
     * @param array{
     *     uuid?: string,
     * } $contentRichEntityIdentifier
     * @param array<string, mixed> $options
     * @return ContentRichEntityInterface<T>
     */
    public function restore(array $contentRichEntityIdentifier, int $version, array $options): ContentRichEntityInterface;

    public function getType(): string;
}
