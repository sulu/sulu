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

namespace Sulu\Content\Application\Visitor;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Excludes the currently rendered content (the "own" page) from its own smart content results.
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own smart content filters visitor to change its behaviour.
 */
class ExcludeSelfSmartContentFiltersVisitor implements SmartContentFiltersVisitorInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function visit(array $data, array $filters, array $parameters): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $filters;
        }

        $object = $request->attributes->get('object');
        if (!$object instanceof DimensionContentInterface) {
            return $filters;
        }

        $id = $object->getResource()->getId();

        /** @var string[] $excluded */
        $excluded = $filters['excluded'] ?? [];
        $excluded[] = (string) $id;
        $filters['excluded'] = \array_values(\array_unique($excluded));

        return $filters;
    }
}
