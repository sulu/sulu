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

namespace Sulu\Content\Tests\Functional\Traits;

use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

trait QueryCountTrait
{
    /**
     * @return array<string, int> the number of SELECT queries per table, queries of other kinds are counted as "other"
     */
    protected static function countQueriesByTable(callable $run): array
    {
        if (!static::getContainer()->has('doctrine.debug_data_holder')) {
            static::markTestSkipped('Counting queries needs the debug data holder of DoctrineBundle 2.7 or later.');
        }

        /** @var DebugDataHolder $debugDataHolder */
        $debugDataHolder = static::getContainer()->get('doctrine.debug_data_holder');
        $debugDataHolder->reset();

        $run();

        $queriesByTable = [];
        /** @var array<string, list<array{sql: string}>> $data */
        $data = $debugDataHolder->getData();
        foreach ($data['default'] ?? [] as $query) {
            \preg_match('/^\s*SELECT\b.*?\bFROM\s+`?(\w+)`?/is', $query['sql'], $matches);
            $table = $matches[1] ?? 'other';
            $queriesByTable[$table] = ($queriesByTable[$table] ?? 0) + 1;
        }

        return $queriesByTable;
    }

    protected static function countQueries(callable $run): int
    {
        return \array_sum(static::countQueriesByTable($run));
    }

    abstract protected static function getContainer(): ContainerInterface;

    abstract protected static function markTestSkipped(string $message = ''): never;
}
