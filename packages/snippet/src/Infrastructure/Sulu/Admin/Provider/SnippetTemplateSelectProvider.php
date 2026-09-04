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

namespace Sulu\Snippet\Infrastructure\Sulu\Admin\Provider;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;

/**
 * @internal
 */
class SnippetTemplateSelectProvider
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider
    ) {
    }

    /**
     * @param string|null $templates comma separated template keys the list is restricted to
     *
     * @return mixed[]
     */
    public function getFilterValues(string $locale, ?string $templates = null): array
    {
        /** @var TypedFormMetadata $metadata */
        $metadata = $this->formMetadataProvider->getMetadata(SnippetDimensionContent::getTemplateType(), $locale, []);

        $templateKeys = null === $templates ? [] : \array_filter(\explode(',', $templates));

        $options = [];
        foreach ($metadata->getForms() as $key => $form) {
            if ([] !== $templateKeys && !\in_array($key, $templateKeys, true)) {
                continue;
            }

            $options[$key] = $form->getTitle($locale);
        }

        return $options;
    }
}
