<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\Visitor;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\Visitor\SmartContentFiltersVisitorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MediaSmartContentFiltersVisitor implements SmartContentFiltersVisitorInterface
{
    public function __construct(
        private RequestAnalyzerInterface $requestAnalyzer,
        private RequestStack $requestStack,
    ) {
    }

    public function visit(array $data, array $filters, array $parameters): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        if ($parameters['mimetype_parameter'] ?? null) {
            $mimetypeParameter = $parameters['mimetype_parameter'];
            if (\is_string($mimetypeParameter)) {
                $filters['mimetype'] = $mimetypeParameter;
            }
        }
        if ($parameters['type_parameter'] ?? null) {
            $typeParameter = $parameters['type_parameter'];
            if (\is_string($typeParameter)) {
                $filters['type'] = $typeParameter;
            }
        }

        $filters['webspaceKey'] = $this->requestAnalyzer->getWebspace()->getKey();

        return $filters;
    }
}
