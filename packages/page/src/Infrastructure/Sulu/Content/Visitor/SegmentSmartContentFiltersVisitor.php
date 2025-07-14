<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\Visitor;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Content\Application\Visitor\SmartContentFiltersVisitorInterface;

class SegmentSmartContentFiltersVisitor implements SmartContentFiltersVisitorInterface
{
    public function __construct(private RequestAnalyzerInterface $requestAnalyzer)
    {
    }

    public function visit(array $data, array $filters, array $parameters): array
    {
        /** @var Segment|null $segment */
        $segment = $this->requestAnalyzer->getSegment(); // @phpstan-ignore function.alreadyNarrowedType
        if (null !== $segment) {
            $filters['segmentKey'] = $segment->getKey();
        }

        return $filters;
    }
}
