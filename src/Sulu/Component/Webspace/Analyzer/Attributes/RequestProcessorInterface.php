<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for request attributes provider.
 */
interface RequestProcessorInterface
{
    /**
     * Returns request attributes for given request.
     *
     * @param Request $request
     * @param RequestAttributes $requestAttributes
     *
     * @return RequestAttributes
     */
    public function process(Request $request, RequestAttributes $requestAttributes);

    /**
     * Validate the given attributes and return true if it is valid,
     * if not throw a specific exception.
     *
     * @param RequestAttributes $attributes
     *
     * @return bool
     */
    public function validate(RequestAttributes $attributes);
}
