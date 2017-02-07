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

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Interface for request analyzer resolver.
 */
interface RequestAnalyzerResolverInterface
{
    /**
     * Resolves the request analyzer to an array.
     *
     * @param RequestAnalyzerInterface $requestAnalyzer
     *
     * @return array
     */
    public function resolve(RequestAnalyzerInterface $requestAnalyzer);
}
