<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Exception;

/**
 * This exception will be thrown when the given webspace does not exist.
 */
class WebspaceLocalizationNotFoundException extends PreviewRendererException
{
    /**
     * @param object $object
     * @param int $id
     * @param string $webspaceKey
     * @param string $locale
     */
    public function __construct($object, $id, $webspaceKey, $locale)
    {
        parent::__construct(
            sprintf('Localization "%s" in webspace "%s" not found', $locale, $webspaceKey),
            self::BASE_CODE + 7,
            $object,
            $id,
            $webspaceKey,
            $locale
        );
    }
}
