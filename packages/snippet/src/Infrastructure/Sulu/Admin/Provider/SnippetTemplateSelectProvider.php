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

class SnippetTemplateSelectProvider
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getFilterValues(string $locale): array
    {
        /** @var TypedFormMetadata $metadata */
        $metadata = $this->formMetadataProvider->getMetadata(SnippetDimensionContent::getTemplateType(), $locale, []);

        $options = [];
        foreach ($metadata->getForms() as $key => $form) {
            $options[$key] = $form->getTitle($locale);
        }

        return $options;
    }
}
