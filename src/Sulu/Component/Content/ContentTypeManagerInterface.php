<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * content type manager interface.
 */
interface ContentTypeManagerInterface
{
    /**
     * returns content type.
     *
     * @param string $contentTypeName The name of the content to load
     *
     * @return ContentTypeInterface
     */
    public function get($contentTypeName);

    /**
     * checks if contentType exists.
     *
     * @param string $contentTypeName
     *
     * @return bool
     */
    public function has($contentTypeName);

    /**
     * returns all content types.
     *
     * @return ContentTypeInterface[]
     */
    public function getAll();
}
