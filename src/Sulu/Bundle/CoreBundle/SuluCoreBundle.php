<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\CoreBundle;

use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterContentTypesCompilerPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterLocalizationProvidersPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RemoveForeignContextServicesPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RequestAnalyzerCompilerPass;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\StructureExtensionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RequestAnalyzerCompilerPass());
        $container->addCompilerPass(new StructureExtensionCompilerPass());
        $container->addCompilerPass(new RegisterContentTypesCompilerPass());
        $container->addCompilerPass(new RegisterLocalizationProvidersPass());
        $container->addCompilerPass(new RemoveForeignContextServicesPass());
    }
}
