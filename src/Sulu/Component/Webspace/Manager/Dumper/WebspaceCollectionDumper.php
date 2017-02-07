<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager\Dumper;

class WebspaceCollectionDumper
{
    protected function render($template, $parameters)
    {
        //TODO set path in a more elegant way
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__ . '/../../Resources/skeleton/'));

        $twig->addFunction(
            new \Twig_SimpleFunction(
                'is_array', function ($value) {
                    return is_array($value);
                }
            )
        );

        return $twig->render($template, $parameters);
    }
}
