<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Routing\Defaults;

/**
 * Provides route-defaults for given entity-class and id.
 */
interface RouteDefaultsProviderInterface
{
    /**
     * Returns route-defaults for given entity-class and id.
     *
     * @param string $entityClass
     * @param string $id
     * @param string $locale
     * @param object|null $object If entity is not null it was already loaded (e.g. preview)
     *
     * @return array
     */
    public function getByEntity($entityClass, $id, $locale, $object = null);

    /**
     * Returns true if object is published.
     *
     * @param string $entityClass
     * @param string $id
     * @param string $locale
     *
     * @return bool
     */
    public function isPublished($entityClass, $id, $locale);

    /**
     * Returns true if this provider supports given entity-class.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function supports($entityClass);
}
