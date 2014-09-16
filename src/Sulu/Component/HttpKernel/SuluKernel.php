<?php

namespace Sulu\Component\HttpKernel;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Base class for all Sulu kernels.
 */
abstract class SuluKernel extends Kernel
{
    private $context = null;

    const CONTEXT_ADMIN = 'admin';

    const CONTEXT_WEBSITE = 'website';

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
     * Set the context
     */
    protected function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     */
    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            array(
                'sulu.context' => $this->getContext(),
            )
        );
    }
}
