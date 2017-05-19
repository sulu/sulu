<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\SmartContent\QueryBuilder;

/**
 * QueryBuilder for snippets.
 */
class SnippetQueryBuilder extends QueryBuilder
{
    protected static $mixinTypes = ['sulu:snippet'];

    protected static $structureType = Structure::TYPE_SNIPPET;
}
