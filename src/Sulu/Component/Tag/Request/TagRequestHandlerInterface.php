<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Tag\Request;

/**
 * Handles tags in current request.
 */
interface TagRequestHandlerInterface
{
    /**
     * Determine tags from current request.
     *
     * @param string $tagsParameter
     *
     * @return string[]
     */
    public function getTags($tagsParameter = 'tags');

    /**
     * Extends current URL with given tag.
     *
     * @param array $tag will be included in the URL.
     * @param string $tagsParameter GET parameter name.
     *
     * @return string
     */
    public function appendTagToUrl($tag, $tagsParameter = 'tags');

    /**
     * Set tag to current URL.
     *
     * @param array $tag will be included in the URL.
     * @param string $tagsParameter GET parameter name.
     *
     * @return string
     */
    public function setTagToUrl($tag, $tagsParameter = 'tags');

    /**
     * Remove tag from current URL.
     *
     * @param string $tagsParameter GET parameter name.
     *
     * @return string
     */
    public function removeTagsFromUrl($tagsParameter = 'tags');
}
