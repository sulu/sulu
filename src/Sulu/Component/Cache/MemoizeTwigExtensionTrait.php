<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

/**
 * Provides functionality to convert twig functions.
 */
trait MemoizeTwigExtensionTrait
{
    /**
     * @var \Twig_ExtensionInterface
     */
    protected $extension;

    /**
     * @var MemoizeInterface
     */
    protected $memoizeCache;

    /**
     * @var int
     */
    protected $lifeTime;

    /**
     * {@see \Twig_Extension::getFunctions}.
     */
    public function getFunctions()
    {
        $result = [];
        foreach ($this->extension->getFunctions() as $function) {
            $callable = $function->getCallable();
            $name = $function->getName();

            $result[] = new \Twig_SimpleFunction(
                $name,
                function () use ($callable, $name) {
                    return $this->memoizeCache->memoizeById($name, func_get_args(), $callable, $this->lifeTime);
                }
            );
        }

        return $result;
    }
}
