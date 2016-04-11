<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager\Dumper;

use Sulu\Component\Webspace\Manager\WebspaceCollection;

class PhpWebspaceCollectionDumper extends WebspaceCollectionDumper
{
    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    public function __construct(WebspaceCollection $webspaceCollection)
    {
        $this->webspaceCollection = $webspaceCollection;
    }

    /**
     * Creates a new class with the data from the given collection.
     *
     * @param array $options
     *
     * @return string
     */
    public function dump($options = [])
    {
        return $this->render(
            'WebspaceCollectionClass.php.twig',
            [
                'cache_class' => $options['cache_class'],
                'base_class' => $options['base_class'],
                'collection' => $this->webspaceCollection->toArray(),
            ]
        );
    }
}
