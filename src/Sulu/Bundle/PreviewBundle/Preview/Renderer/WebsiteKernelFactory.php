<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

use Sulu\Component\HttpKernel\SuluKernel;

/**
 * Creates new Website-Kernels foreach preview request.
 */
class WebsiteKernelFactory implements KernelFactoryInterface
{
    public function __construct(private string $environment)
    {
    }

    public function create()
    {
        $kernel = new PreviewKernel($this->environment, 'dev' === $this->environment, SuluKernel::CONTEXT_WEBSITE);
        $kernel->boot();

        return $kernel;
    }
}
