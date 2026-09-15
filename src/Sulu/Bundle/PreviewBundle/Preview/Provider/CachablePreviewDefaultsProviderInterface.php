<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Provider;

use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;

/**
 * Keeps the state of the defaults between two requests of the same preview session.
 *
 * Without it, the defaults are loaded again with getDefaults() on every request, and the values
 * which were set with updateValues() or updateContext() get lost when the preview is rendered again.
 */
interface CachablePreviewDefaultsProviderInterface extends PreviewDefaultsProviderInterface
{
    /**
     * Serializes the defaults to store them in the preview cache.
     *
     * @param array<string, mixed> $defaults
     */
    public function serialize(PreviewContext $previewContext, array $defaults): string;

    /**
     * Restores the defaults from the value returned by serialize().
     *
     * @return array<string, mixed>
     */
    public function deserialize(PreviewContext $previewContext, string $serializedDefaults): array;
}
