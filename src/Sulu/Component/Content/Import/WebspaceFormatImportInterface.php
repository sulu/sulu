<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import;

/**
 * Defines the methods for the import services.
 */
interface WebspaceFormatImportInterface
{
    /**
     * Will parse the given file and return a documents array.
     *
     * @param string $filePath
     * @param string $locale
     *
     * @return array
     */
    public function parse($filePath, $locale);

    /**
     * Will return the correct property value by the parsed data.
     *
     * @param string $name
     * @param array $data
     * @param string $contentTypeName
     * @param string $extension
     * @param mixed $default
     *
     * @return mixed
     */
    public function getPropertyData($name, $data, $contentTypeName = null, $extension = null, $default = null);

    /**
     * Will return the correct property value by the parsed data.
     *
     * @param string $name
     * @param array $data
     * @param string $contentTypeName
     * @param string $extension
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty($name, $data, $contentTypeName = null, $extension = null, $default = null);
}
