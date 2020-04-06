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
    /**
     * @var SuluKernel
     */
    protected $kernel;

    public function __construct(SuluKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function create()
    {
        $kernel = $this->kernel->createPreviewKernel();
        $kernel->boot();

        return $kernel;
    }
}
