<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Exception;

/**
 * This exception will be thrown when no route-defaults-provider exists for given object.
 */
class RouteDefaultsProviderNotFoundException extends PreviewRendererException
{
    /**
     * @param string $object
     * @param int $id
     * @param mixed $webspaceKey
     * @param string $locale
     */
    public function __construct($object, $id, $webspaceKey, $locale)
    {
        parent::__construct(
            sprintf('RouteDefaultsProvider for "%s" not found.', get_class($object)),
            self::BASE_CODE + 2,
            $object,
            $id,
            $webspaceKey,
            $locale
        );
    }
}
