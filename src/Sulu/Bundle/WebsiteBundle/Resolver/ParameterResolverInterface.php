<?php

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
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Interface to resolve parameters for website rendering.
 */
interface ParameterResolverInterface
{
    /**
     * Resolves parameter for website controller.
     *
     * @param bool $preview
     */
    public function resolve(
        array $parameter,
        ?RequestAnalyzerInterface $requestAnalyzer = null,
        ?StructureInterface $structure = null,
        $preview = false
    );
}
