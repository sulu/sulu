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
     * @var \Twig_Extension
     */
    protected $extension;

    /**
     * {@see \Twig_Extension::getFunctions}.
     */
    public function getFunctions()
    {
        $result = [];
        foreach ($this->extension->getFunctions() as $function) {
            $callable = $function->getCallable();
            if (is_array($callable)) {
                $callable[0] = $this;
            }

            $result[] = new \Twig_SimpleFunction($function->getName(), $callable);
        }

        return $result;
    }
}
