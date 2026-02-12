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

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

/**
 * @internal
 */
trait LinkUrlTrait
{
    /**
     * @param array<string, mixed> $linkData
     */
    private function appendQueryAndAnchor(string $url, array $linkData): string
    {
        if (isset($linkData['query']) && \is_string($linkData['query'])) {
            $url = \sprintf('%s?%s', $url, \ltrim($linkData['query'], '?'));
        }
        if (isset($linkData['anchor']) && \is_string($linkData['anchor'])) {
            $url = \sprintf('%s#%s', $url, \ltrim($linkData['anchor'], '#'));
        }

        return $url;
    }
}
