<?php

use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();
$collection->addCollection(
    $loader->import('@SuluSearchBundle/Resources/config/routing.yml')
);

return $collection;
