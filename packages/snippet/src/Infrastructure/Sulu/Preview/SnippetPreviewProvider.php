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

namespace Sulu\Snippet\Infrastructure\Sulu\Preview;

use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\UserInterface\Controller\Website\SnippetPreviewController;

/**
 * @extends ContentObjectProvider<SnippetDimensionContentInterface, SnippetInterface>
 */
class SnippetPreviewProvider extends ContentObjectProvider
{
    public function getDefaults(PreviewContext $previewContext): array
    {
        return $this->applyMissingViewFallback(parent::getDefaults($previewContext));
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return array<string, mixed>
     */
    protected function applyMissingViewFallback(array $defaults): array
    {
        if ([] === $defaults || null !== ($defaults['_controller'] ?? null)) {
            return $defaults;
        }

        // A snippet template that declares no view has nothing to render. The controller below
        // says so in the pane, which beats failing the request on a missing controller.
        $object = $defaults['object'] ?? null;

        $defaults['_controller'] = SnippetPreviewController::class . '::indexAction';
        $defaults['view'] = null;
        $defaults['templateKey'] = $object instanceof TemplateInterface ? $object->getTemplateKey() : null;

        return $defaults;
    }
}
