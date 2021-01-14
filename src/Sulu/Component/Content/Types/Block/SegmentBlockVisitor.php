<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Block;

use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class SegmentBlockVisitor implements BlockVisitorInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function visit(BlockPropertyType $block): ?BlockPropertyType
    {
        $blockPropertyTypeSettings = $block->getSettings();

        $webspace = $this->requestAnalyzer->getWebspace();
        $webspaceKey = $webspace ? $webspace->getKey() : null;
        $segment = $this->requestAnalyzer->getSegment();

        if (\is_array($blockPropertyTypeSettings)
            && $webspaceKey
            && isset($blockPropertyTypeSettings['segment_enabled'])
            && $blockPropertyTypeSettings['segment_enabled']
            && isset($blockPropertyTypeSettings['segments'][$webspaceKey])
            && $segment
            && $blockPropertyTypeSettings['segments'][$webspaceKey] !== $segment->getKey()
        ) {
            return null;
        } else {
            return $block;
        }
    }
}
