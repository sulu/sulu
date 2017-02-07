<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle;

use Sulu\Bundle\WebsocketBundle\DependencyInjection\Compiler\AddMessageDispatcherPass;
use Sulu\Bundle\WebsocketBundle\DependencyInjection\Compiler\AddWebsocketAppPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry point of websocket bundle.
 */
class SuluWebsocketBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddWebsocketAppPass());
        $container->addCompilerPass(new AddMessageDispatcherPass());
    }
}
