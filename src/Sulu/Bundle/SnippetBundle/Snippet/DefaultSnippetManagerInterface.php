<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Webspace\Webspace;

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

    /**
     * Returns type of given uuid.
     *
     * @param string $uuid
     *
     * @return string
     */
    public function loadType($uuid);

    /**
     * Returns all webspaces for which the snippet with the given ID is assigned as default snippet.
     *
     * @param string $uuid
     *
     * @return Webspace[]
     */
    public function loadWebspaces($uuid);
}
