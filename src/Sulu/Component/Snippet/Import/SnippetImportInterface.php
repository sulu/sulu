<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Snippet\Import;

use Sulu\Component\Import\Exception\FormatImporterNotFoundException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import Snippets by given xliff-file.
 */
interface SnippetImportInterface
{
    /**
     * Import Snippet by given XLIFF-File.
     *
     * @param string $locale
     * @param string $filePath
     * @param string $format
     *
     * @return \stdClass
     *
     * @throws FormatImporterNotFoundException
     */
    public function import($locale, $filePath, ?OutputInterface $output = null, $format = '1.2.xliff');
}
