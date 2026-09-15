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

namespace Sulu\Content\Infrastructure\Symfony\Twig\Runtime;

use Sulu\Content\Domain\Table\TableData;
use Sulu\Content\Domain\Table\TableView;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Holds the logic for the `sulu_resolve_table` Twig function. Lazily
 * instantiated by Twig only when the function is actually used.
 */
final class TableRuntime implements RuntimeExtensionInterface
{
    /**
     * Normalizes any stored/raw value into a template-ready {@see TableView}.
     */
    public function resolveTableFunction(mixed $value): TableView
    {
        return TableView::fromData(TableData::fromArray($value));
    }
}
