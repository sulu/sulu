<?php

declare(strict_types=1);

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
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\Visitor\SmartContentFiltersVisitorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own smart content filters visitor to change its behaviour.
 */
class WebspaceSmartContentFiltersVisitor implements SmartContentFiltersVisitorInterface
{
    public function __construct(
        private RequestAnalyzerInterface $requestAnalyzer,
        private RequestStack $requestStack,
    ) {
    }

    public function visit(array $data, array $filters, array $parameters): array
    {
        if ('true' === ($parameters['ignoreWebspaces'] ?? null) || true === ($parameters['ignoreWebspaces'] ?? null)) {
            return $filters;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $filters;
        }

        $webspace = $this->requestAnalyzer->getWebspace();

        if ($webspace instanceof Webspace) { // @phpstan-ignore-line instanceof.alwaysTrue
            $filters['webspaceKey'] = $webspace->getKey();
        }

        return $filters;
    }
}
