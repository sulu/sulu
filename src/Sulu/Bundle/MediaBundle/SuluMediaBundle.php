<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle;

use Sulu\Bundle\MediaBundle\DependencyInjection\FormatCacheClearerCompilerPass;
use Sulu\Bundle\MediaBundle\DependencyInjection\ImageCommandCompilerPass;
use Sulu\Bundle\MediaBundle\DependencyInjection\ImageFormatCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluMediaBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FormatCacheClearerCompilerPass());
        $container->addCompilerPass(new ImageFormatCompilerPass());
        $container->addCompilerPass(new ImageCommandCompilerPass());
    }
}
