<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures loaders to use legacy (pre-3.0) XSD schemas when legacy_length is enabled,
 * allowing webspace and template keys longer than 31 characters.
 *
 * @internal
 */
class LegacyLengthCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('sulu.persistence.legacy_length')) {
            return;
        }

        if ($container->hasDefinition('sulu_admin.form_metadata.template_xml_loader')) {
            $container->getDefinition('sulu_admin.form_metadata.template_xml_loader')
                ->addArgument('/schema/template-1.0-legacy.xsd');
        }

        if ($container->hasDefinition('sulu_core.webspace.loader.xml.1.0')) {
            $container->getDefinition('sulu_core.webspace.loader.xml.1.0')
                ->addArgument('/schema/webspace/webspace-1.0-legacy.xsd');
        }

        if ($container->hasDefinition('sulu_core.webspace.loader.xml.1.1')) {
            $container->getDefinition('sulu_core.webspace.loader.xml.1.1')
                ->addArgument('/schema/webspace/webspace-1.1-legacy.xsd');
        }
    }
}
