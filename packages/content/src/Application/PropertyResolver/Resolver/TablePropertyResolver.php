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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Table\TableData;

/**
 * Resolves the `table` content type into a normalized, rectangular structure so
 * templates always receive consistent `head`/`body` data, even for legacy or
 * malformed stored values.
 */
final readonly class TablePropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        return ContentView::create(TableData::fromArray($data)->toArray(), []);
    }

    public static function getType(): string
    {
        return 'table';
    }
}
