<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template\Dumper;


class TemplateDumper
{
    protected function render($template, $parameters)
    {
        //TODO set path in a more elegant way
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__ . '/../Resources/Skeleton/'));

        return $twig->render($template, $parameters);
    }
}
