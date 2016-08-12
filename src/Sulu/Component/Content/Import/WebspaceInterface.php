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

use Sulu\Component\Content\Import\Exception\WebspaceFormatImporterNotFoundException;

/**
 * Interface for Webspace import.
 */
interface WebspaceInterface
{
    /**
     * @param WebspaceFormatImportInterface $service
     * @param $format
     */
    public function add($service, $format);

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $filePath
     * @param string $format
     * @param string $uuid
     *
     * @return array
     *
     * @throws WebspaceFormatImporterNotFoundException
     */
    public function import(
        $webspaceKey,
        $locale,
        $filePath,
        $format = '1.2.xliff',
        $uuid = null
    );
}
