<?php

use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();
$collection->addCollection(
    $loader->import('@SuluSnippetBundle/Resources/config/routing_api.yml')
);

return $collection;
