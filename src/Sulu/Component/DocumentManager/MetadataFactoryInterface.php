<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;

interface MetadataFactoryInterface
{
    /**
     * Return metadata for the given alias.
     *
     * @param string $alias
     *
     * @return Metadata
     */
    public function getMetadataForAlias($alias);

    /**
     * Return metadata for the given PHPCR type (e.g. sulu:page).
     *
     * @param string $phpcrType
     *
     * @return Metadata
     */
    public function getMetadataForPhpcrType($phpcrType);

    /**
     * Return true if there is metadata for the given PHPCR type.
     *
     * @param string $phpcrType
     *
     * @return bool
     */
    public function hasMetadataForPhpcrType($phpcrType);

    /**
     * Return the metadata for the PHPCR node. If the PHPCR node is not managed
     * then the Metadata should be that of the Sulu\Component\DocumentManager\Document\UnknownDocument.
     *
     * @param NodeInterface $phpcrNode
     */
    public function getMetadataForPhpcrNode(NodeInterface $phpcrNode);

    /**
     * Return metadata for the given class.
     *
     * @param mixed $class
     *
     * @return Metadata
     */
    public function getMetadataForClass($class);

    /**
     * Return true if the document has metadata for the given fully qualified
     * class name.
     *
     * @param string
     *
     * @return bool
     */
    public function hasMetadataForClass($class);

    /**
     * Return the metadata for all managed document classes.
     *
     * @return Metadata[]
     */
    public function getAllMetadata();

    /**
     * Return true if the given alias exists.
     *
     * @param string $alias
     *
     * @return bool
     */
    public function hasAlias($alias);

    /**
     * Return all registered aliases.
     *
     * @return array
     */
    public function getAliases();
}
