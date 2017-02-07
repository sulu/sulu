<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @param array                    $parameter
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param StructureInterface       $structure
     * @param bool                     $preview
     *
     * @return mixed
     */
    public function resolve(
        array $parameter,
        RequestAnalyzerInterface $requestAnalyzer = null,
        StructureInterface $structure = null,
        $preview = false
    );
}
