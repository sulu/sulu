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

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class TimingStructureResolver implements StructureResolverInterface
{
    public function __construct(
        private readonly StructureResolverInterface $structureResolver,
        private readonly Stopwatch $stopwatch
    ) {
    }

    public function resolve(StructureInterface $structure, bool $loadExcerpt = true/*, ?array $includedProperties = null*/)
    {
        $includedProperties = (\func_num_args() > 2) ? \func_get_arg(2) : null;

        $name = \sprintf('Resolving structure "%s"', $structure->getNodeName());
        $category = 'sulu_content';
        $this->stopwatch->start($name, $category);
        $result = $this->structureResolver->resolve($structure, $loadExcerpt, $includedProperties);
        $this->stopwatch->stop($name, $category);

        return $result;
    }
}
