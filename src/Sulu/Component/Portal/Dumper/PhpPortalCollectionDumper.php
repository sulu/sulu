<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal\Dumper;

use Sulu\Component\Portal\PortalCollection;

class PhpPortalCollectionDumper extends PortalCollectionDumper
{
    /**
     * @var PortalCollection
     */
    private $portalCollection;

    public function __construct(PortalCollection $portalCollection)
    {
        $this->portalCollection = $portalCollection;
    }

    /**
     * Creates a new class with the data from the given collection
     * @param array $options
     * @return string
     */
    public function dump($options = array())
    {
        return $this->render(
            'PortalCollectionClass.php.twig',
            array(
                'cache_class' => $options['cache_class'],
                'base_class' => $options['base_class'],
                'portals' => $this->portalCollection->toArray()
            )
        );
    }
}
