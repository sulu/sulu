<?php

namespace Sulu\Bundle\DocumentManagerBundle;

use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\InitializerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluDocumentManagerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new InitializerPass());
        $container->addCompilerPass(new RegisterListenersPass(
            'sulu_document_manager.event_dispatcher',
            'sulu_document_manager.event_listener',
            'sulu_document_manager.event_subscriber'
        ));
    }
}
