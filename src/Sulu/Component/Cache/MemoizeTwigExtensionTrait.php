<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * Provides functionality to convert twig functions.
 */
trait MemoizeTwigExtensionTrait
{
    /**
     * @var ExtensionInterface
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

    public function getFunctions()
    {
        $result = [];
        foreach ($this->extension->getFunctions() as $function) {
            /** @var callable $callable */
            $callable = $function->getCallable();
            $name = $function->getName();

            $result[] = new TwigFunction(
                $name,
                function() use ($callable, $name) {
                    return $this->memoizeCache->memoizeById($name, \func_get_args(), $callable, $this->lifeTime);
                }
            );
        }

        return $result;
    }
}
