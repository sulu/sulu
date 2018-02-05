<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Snippet\Export;

/**
 * Interface for Snippet export.
 */
interface SnippetExportInterface
{
    /**
     * Export all data from snippet by given locale.
     *
     * @param string $locale
     * @param $output
     * @param string $format
     *
     * @return array
     */
    public function export($locale, $output, $format = '1.2.xliff');
}
