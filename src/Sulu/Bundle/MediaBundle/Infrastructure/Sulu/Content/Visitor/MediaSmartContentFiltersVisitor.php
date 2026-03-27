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
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\Visitor\SmartContentFiltersVisitorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own smart content filters visitor to change its behaviour.
 */
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
            return $filters;
        }

        if ($parameters['mimetype_parameter'] ?? null) {
            $mimetypeParameter = $parameters['mimetype_parameter'];
            if (\is_string($mimetypeParameter)) {
                $filters['mimetype'] = $request->query->getString($mimetypeParameter);
            }
        }

        if ($parameters['type_parameter'] ?? null) {
            $typeParameter = $parameters['type_parameter'];
            if (\is_string($typeParameter)) {
                $filters['type'] = $request->query->getString($typeParameter);
            }
        }

        if ('true' === ($parameters['ignoreWebspaces'] ?? null) || true === ($parameters['ignoreWebspaces'] ?? null)) {
            return $filters;
        }

        $webspace = $this->requestAnalyzer->getWebspace();

        if ($webspace instanceof Webspace) { // @phpstan-ignore-line instanceof.alwaysTrue
            $filters['webspaceKey'] = $webspace->getKey();
        }

        return $filters;
    }
}
