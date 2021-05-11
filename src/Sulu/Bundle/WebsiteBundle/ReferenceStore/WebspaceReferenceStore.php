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
use Sulu\Component\Webspace\Webspace;

class WebspaceReferenceStore implements ReferenceStoreInterface
{
    const WEBSPACE_REFERENCE_ALIAS = 'webspace';

    public static function generateTagByWebspaceKey(string $webspaceKey): string
    {
        return \sprintf('%s-%s', self::WEBSPACE_REFERENCE_ALIAS, $webspaceKey);
    }

    public static function getWebspaceKeyFromTag(string $tag): string
    {
        $parts = \explode('-', $tag, 2);

        if (!isset($parts[1]) || $parts[0] !== static::WEBSPACE_REFERENCE_ALIAS) {
            throw new \InvalidArgumentException(
                'Tag must be set and has to start with "' . static::WEBSPACE_REFERENCE_ALIAS . '-".'
            );
        }

        return $parts[1];
    }

    /**
     * @var RequestAnalyzerInterface|null
     */
    private $requestAnalyzer;

    public function __construct(?RequestAnalyzerInterface $requestAnalyzer = null)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * @param string $id
     */
    public function add($id): void
    {
        throw new \LogicException('Webspace tags cannot be set manually. They are set to match the current webspace automatically.');
    }

    /**
     * @return string[]
     */
    public function getAll()
    {
        if (!$this->requestAnalyzer) {
            return [];
        }

        /** @var Webspace|null $webspace */
        $webspace = $this->requestAnalyzer->getWebspace();
        if (!$webspace) {
            return [];
        }

        return [$webspace->getKey()];
    }
}
