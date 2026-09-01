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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Resolver;

use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader\ExampleResourceLoader;

/**
 * Test double for a resolver placed at `[root][exampleRoot][content]`, active only for the `root-resolver-example` template.
 */
final class ExampleRootResolver implements ResolverInterface
{
    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        if (!$dimensionContent instanceof TemplateInterface
            || 'root-resolver-example' !== $dimensionContent->getTemplateKey()
        ) {
            return null;
        }

        /** @var array{related?: list<int>} $templateData */
        $templateData = $dimensionContent->getTemplateData();
        $relatedId = $templateData['related'][0] ?? null;

        return ContentView::create(
            [
                'code' => 'ROOT-' . $dimensionContent->getResource()->getId(),
                'related' => null !== $relatedId
                    ? ContentView::createResolvable($relatedId, ExampleResourceLoader::getKey(), [])
                    : null,
            ],
            ['dropped' => 'unused'],
        );
    }
}
