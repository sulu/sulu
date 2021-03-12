<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ReferenceStore;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class WebspaceReferenceStore implements ReferenceStoreInterface
{
    const WEBSPACE_REFERENCE_ALIAS = 'webspace';

    /**
     * @var RequestAnalyzerInterface|null
     */
    private $requestAnalyzer;

    public function __construct(?RequestAnalyzerInterface $requestAnalyzer = null)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function add($id)
    {
        throw new \LogicException('Should never be called');
    }

    public function getAll()
    {
        if (!$this->requestAnalyzer) {
            return [];
        }

        $webspace = $this->requestAnalyzer->getWebspace();
        if (!$webspace) {
            return [];
        }

        return [$webspace->getKey()];
    }
}
