<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
interface PreviewRendererInterface
{
    /**
     * Renders object in given webspace and locale.
     *
     * @param array<string, mixed> $object
     * @param array<string, mixed> $options
     *
     * @return string
     */
    public function render(
        array $object,
        string $id,
        bool $partial = false,
        array $options = []
    );
}
