<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

/**
 * Interface for key-value pairs
 */
interface KeyTitlePairInterface
{
    /**
     * Returns key of item.
     *
     * @return string
     */
    public function getKey();

    /**
     * Returns title of item.
     *
     * @param string $locale Locale of title.
     *
     * @return mixed
     */
    public function getTitle($locale);
}
