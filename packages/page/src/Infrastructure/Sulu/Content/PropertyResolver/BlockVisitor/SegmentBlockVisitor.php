<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\PropertyResolver\BlockVisitor\BlockVisitorInterface;

class SegmentBlockVisitor implements BlockVisitorInterface
{
    public function __construct(private RequestAnalyzerInterface $requestAnalyzer)
    {
    }

    public function visit(array $block): ?array
    {
        $blockPropertyTypeSettings = $block['settings'] ?? [];

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
