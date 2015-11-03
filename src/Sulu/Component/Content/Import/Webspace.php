<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import;

use Sulu\Component\Content\Import\Exception\WebspaceFormatImporterNotFoundException;

class Webspace implements WebspaceInterface
{
    /**
     * @var WebspaceFormatImportInterface[]
     */
    protected $formatServices = [];

    /**
     * {@inheritdoc}
     */
    public function add(WebspaceFormatImportInterface $service, $format)
    {
        $this->formatServices[$format] = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function import(
        $webspaceKey,
        $locale,
        $filePath,
        $format = '1.2.xliff'
    ) {
        $data = $this->getImporter($format)->import($filePath);
    }

    /**
     * @param $format
     *
     * @return WebspaceFormatImportInterface
     *
     * @throws WebspaceFormatImporterNotFoundException
     */
    public function getImporter($format)
    {
        if (!isset($this->formatServices[$format])) {
            throw new WebspaceFormatImporterNotFoundException($format);
        }

        return $this->formatServices[$format];
    }
}
