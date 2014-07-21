<?php

namespace Sulu\Bundle\LocationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GeolocatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sulu_location.geolocator.manager')) {
            return;
        }

        $geolocationManagerDef = $container->getDefinition('sulu_location.geolocator.manager');
        $ids = $container->findTaggedServiceIds('sulu_location.geolocator');

        foreach ($ids as $id => $attributes) {
            if (!isset($attributes[0]['alias'])) {
                throw new \InvalidArgumentException(sprintf(
                    'No "alias" specified for geolocator with service ID: "%s"',
                    $id
                ));
            }

            $geolocationManagerDef->addMethodCall(
                'register',
                array($attributes[0]['alias'], $id)
            );
        }
    }
}
