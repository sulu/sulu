<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;

/**
 * Manages default snippets.
 */
interface DefaultSnippetManagerInterface
{
    /**
     * Save default snippet for given type.
     *
     * @param string $webspaceKey
     * @param string $type
     * @param string $uuid
     * @param string $locale
     *
     * @return SnippetDocument
     *
     * @throws WrongSnippetTypeException
     */
    public function save($webspaceKey, $type, $uuid, $locale);

    /**
     * Remove default snippet for given type.
     *
     * @param string $webspaceKey
     * @param string $type
     */
    public function remove($webspaceKey, $type);

    /**
     * Load default snippet for given type.
     *
     * @param string $webspaceKey
     * @param string $type
     * @param string $locale
     *
     * @return SnippetDocument
     *
     * @throws WrongSnippetTypeException
     */
    public function load($webspaceKey, $type, $locale);

    /**
     * Loads identifier for given type.
     *
     * @param string $webspaceKey
     * @param string $type
     *
     * @return string
     */
    public function loadIdentifier($webspaceKey, $type);

    /**
     * Returns true if given uuid is a default snippet.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function isDefault($uuid);
}
