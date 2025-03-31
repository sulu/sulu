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

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Creates new Kernels foreach preview request.
 *
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
interface KernelFactoryInterface
{
    /**
     * Create new kernel for a single preview master-request.
     *
     * @return KernelInterface
     */
    public function create();
}
