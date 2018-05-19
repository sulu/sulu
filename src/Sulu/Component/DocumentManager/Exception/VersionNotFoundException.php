<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Exception;

class VersionNotFoundException extends DocumentManagerException
{
    /**
     * @var object
     */
    private $document;

    /**
     * @var int
     */
    private $version;

    /**
     * @param object $document
     * @param string $version
     */
    public function __construct($document, $version)
    {
        parent::__construct(
            sprintf('Version "%s" for document "%s" not found', $version, $document->getUuid())
        );
        $this->document = $document;
        $this->version = $version;
    }

    /**
     * The document, which was tried to restore.
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * The version, which was tried to restore.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
