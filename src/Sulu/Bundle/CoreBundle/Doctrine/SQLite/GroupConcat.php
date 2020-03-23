<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @see Oro\ORM\Query\AST\FunctionFactory::create()
 */

namespace Oro\ORM\Query\AST\Platform\Functions\Sqlite;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\SqlWalker;
use Oro\ORM\Query\AST\Functions\String\GroupConcat as Base;
use Oro\ORM\Query\AST\Platform\Functions\PlatformFunctionNode;

class GroupConcat extends PlatformFunctionNode
{
    /**
     * @see Oro\ORM\Query\AST\Platform\Functions\Mysql\GroupConcat::getSql()
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $isDistinct = !empty($this->parameters[Base::DISTINCT_KEY]);
        $result = 'GROUP_CONCAT(' . ($isDistinct ? 'DISTINCT ' : '');

        $fields = [];
        /** @var Node[] $pathExpressions */
        $pathExpressions = $this->parameters[Base::PARAMETER_KEY];
        foreach ($pathExpressions as $pathExp) {
            $fields[] = $pathExp->dispatch($sqlWalker);
        }

        $result .= sprintf('%s', implode(', ', $fields));

        if (!empty($this->parameters[Base::ORDER_KEY])) {
            $result .= ' ' . $sqlWalker->walkOrderByClause($this->parameters[Base::ORDER_KEY]);
        }

        if (isset($this->parameters[Base::SEPARATOR_KEY])) {
            $result .= ', ' . $sqlWalker->walkStringPrimary($this->parameters[Base::SEPARATOR_KEY]);
        }

        $result .= ')';

        return $result;
    }
}
