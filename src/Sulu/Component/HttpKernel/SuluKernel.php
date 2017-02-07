<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpKernel;

use Symfony\Component\HttpKernel\Kernel;

/**
 * Base class for all Sulu kernels.
 */
abstract class SuluKernel extends Kernel
{
    private $context = null;

    const CONTEXT_ADMIN = 'admin';

    const CONTEXT_WEBSITE = 'website';

    /**
     * Overload the parent constructor method to add an additional
     * constructor argument.
     *
     * {@inheritdoc}
     *
     * @param string $environment
     * @param bool   $debug
     * @param string $suluContext The Sulu context (self::CONTEXT_ADMIN, self::CONTEXT_WEBSITE)
     */
    public function __construct($environment, $debug, $suluContext = self::CONTEXT_ADMIN)
    {
        $this->setContext($suluContext);

        parent::__construct($environment, $debug);
    }

    /**
     * Return the application context.
     *
     * The context indicates to the runtime code which
     * front controller has been accessed (e.g. website or admin)
     */
    protected function getContext()
    {
        if (null === $this->context) {
            throw new \RuntimeException(
                sprintf(
                    'No context has been set for kernel "%s"',
                    get_class($this)
                )
            );
        }

        return $this->context;
    }

    /**
     * Set the context.
     */
    protected function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            ['sulu.context' => $this->getContext()]
        );
    }
}
