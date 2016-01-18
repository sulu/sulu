<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Url;

/**
 * Factory for url-replacer.
 */
class ReplacerFactory implements ReplacerFactoryInterface
{
    /**
     * Returns new url-replacer.
     *
     * @param string $url
     *
     * @return ReplacerInterface
     */
    public function create($url)
    {
        return new Replacer($url);
    }
}
