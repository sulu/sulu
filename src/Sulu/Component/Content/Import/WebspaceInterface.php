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
     * @param $output
     * @param string $format
     * @param string $uuid
     * @return array
     */
    public function import(
        $webspaceKey,
        $locale,
        $filePath,
        $output,
        $format = '1.2.xliff',
        $uuid = null,
        $exportSuluVersion = '1.3'
    );
}
