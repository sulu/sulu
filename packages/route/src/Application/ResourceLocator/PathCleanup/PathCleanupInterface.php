<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\ResourceLocator\PathCleanup;

interface PathCleanupInterface
{
    public const DEFAULT_REPLACERS = [
        'Ä' => 'AE',
        'ä' => 'ae',
        'Ö' => 'OE',
        'ö' => 'oe',
        'Ü' => 'UE',
        'ü' => 'ue',
    ];

    public const DE_REPLACERS = [
        '&' => 'und',
    ];

    public const EN_REPLACERS = [
        '&' => 'and',
    ];

    public const FR_REPLACERS = [
        '&' => 'et',
    ];

    public const IT_REPLACERS = [
        '&' => 'e',
    ];

    public const NL_REPLACERS = [
        '&' => 'en',
    ];

    public const ES_REPLACERS = [
        '&' => 'y',
    ];

    public const BG_REPLACERS = [
        '&' => 'и',
    ];

    public function cleanup(string $path, string $locale): string;
}
