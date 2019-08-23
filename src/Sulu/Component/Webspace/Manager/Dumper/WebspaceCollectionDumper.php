<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager\Dumper;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class WebspaceCollectionDumper
{
    protected function render($template, $parameters)
    {
        //TODO set path in a more elegant way
        $twig = new Environment(new FilesystemLoader(__DIR__ . '/../../Resources/skeleton/'));

        return $twig->render($template, $parameters);
    }
}
