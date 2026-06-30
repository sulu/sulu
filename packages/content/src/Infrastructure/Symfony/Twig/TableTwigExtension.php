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

namespace Sulu\Content\Infrastructure\Symfony\Twig;

use Sulu\Content\Infrastructure\Symfony\Twig\Runtime\TableRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the `table` content type to templates following the Sulu naming
 * convention (cf. sulu_resolve_media). The actual work is delegated to the
 * lazily loaded {@see TableRuntime}.
 */
final class TableTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_resolve_table', [TableRuntime::class, 'resolveTableFunction']),
        ];
    }
}
