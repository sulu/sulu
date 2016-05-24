<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Profiler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Disables profiler for websocket preview requests.
 */
class PreviewProfilerMatcher implements RequestMatcherInterface
{
    /**
     * {@inheritdoc}
     */
    public function matches(Request $request)
    {
        return $request->get('_profiler', true);
    }
}
