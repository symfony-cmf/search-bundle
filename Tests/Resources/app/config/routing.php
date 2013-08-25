<?php

use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();
$collection->addCollection(
    $loader->import(__DIR__.'/routing/phpcr/search.yml')
);

return $collection;
