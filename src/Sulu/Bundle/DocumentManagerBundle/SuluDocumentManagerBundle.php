<?php

namespace Sulu\Bundle\DocumentManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\InitializerPass;
use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\SubscriberPass;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

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
