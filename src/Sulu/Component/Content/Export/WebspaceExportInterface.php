<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for Webspace export.
 */
interface WebspaceExportInterface
{
    /**
     * Export all data from given webspace and locale.
     *
     * @param string $webspaceKey
     * @param string $locale
     * @param OutputInterface $output
     * @param string $format
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return string
     */
    public function export(
        $webspaceKey,
        $locale,
        $output,
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    );

    /**
     * Load all content, extension and settings from given webspace and locale.
     *
     * @param string $webspaceKey
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return array<mixed>
     */
    public function getExportData(
        $webspaceKey,
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    );
}
