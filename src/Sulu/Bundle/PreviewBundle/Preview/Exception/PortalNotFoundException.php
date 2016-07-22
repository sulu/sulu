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
 * This exception will be thrown when no portal exists for given locale and webspace.
 */
class PortalNotFoundException extends PreviewRendererException
{
    public function __construct($object, $id, $webspaceKey, $locale)
    {
        parent::__construct(
            sprintf('Portal for combination of webspace "%s" and locale "%s" not found.', $webspaceKey, $locale),
            self::BASE_CODE + 1,
            $object,
            $id,
            $webspaceKey,
            $locale
        );
    }
}
