<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Application;

use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;

/**
 * @internal only for tests
 */
final readonly class ExamplePreviewDefaultsProvider implements PreviewDefaultsProviderInterface
{
    public function getDefaults(PreviewContext $previewContext): array
    {
        return [
            '_controller' => TemplateController::class . '::templateAction',
            'template' => 'example.html.twig',
            'previewContext' => $previewContext,
        ];
    }

    public function updateValues(PreviewContext $previewContext, array $defaults, array $data): array
    {
        return [
            ...$defaults,
            ...$data,
        ];
    }

    public function updateContext(PreviewContext $previewContext, array $defaults, array $context): array
    {
        return [
            ...$defaults,
            ...$context,
        ];
    }

    public function getSecurityContext(PreviewContext $previewContext): ?string
    {
        return null;
    }
}
