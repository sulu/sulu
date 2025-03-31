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

interface PreviewDefaultsProviderInterface
{
    /**
     * Returns the Route Defaults to render the preview. Should at least return the '_controller' key.
     *
     * @example
     *
     * ```php
     *     return [
     *          'object' => $object,
     *           '_controller' => MyController::class . '::myAction',
     *           'view' => 'my-view',
     *     ];
     * ```
     *
     * @return array<string, mixed>
     */
    public function getDefaults(PreviewContext $previewContext): array;

    /**
     * Called before rendering the preview — can be used to update the values of the defaults.
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function updateValues(PreviewContext $previewContext, array $defaults, array $data): array;

    /**
     * Called before rendering the preview — called example when template is switched.
     * Can be used to update the values of the defaults.
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function updateContext(PreviewContext $previewContext, array $defaults, array $context): array;

    public function getSecurityContext(PreviewContext $previewContext): ?string;
}
