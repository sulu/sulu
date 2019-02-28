<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import;

/**
 * Interface for Webspace import.
 */
interface WebspaceImportInterface
{
    /**
     * Starts language import for given webspace and locale.
     *
     * @param string $webspaceKey
     * @param string $locale
     * @param string $filePath
     * @param $output
     * @param string $format
     * @param string $uuid
     * @param bool $overrideSettings
     * @param string $exportSuluVersion
     *
     * @return array
     */
    public function import(
        $webspaceKey,
        $locale,
        $filePath,
        $output = null,
        $format = '1.2.xliff',
        $uuid = null,
        $overrideSettings = false,
        $exportSuluVersion = '1.3'
    );
}
