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

namespace Sulu\Content\Infrastructure\Sulu\Form;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;
use Sulu\Content\Application\ContentDataMapper\DataMapper\TemplateDataMapper;

/**
 * The URL of `route` and `page_tree_route` fields is owned by the Route entity and
 * derived on read by RoutableTemplateResolver, so it must not be persisted into
 * templateData. This visitor tags those fields so TemplateDataMapper skips them.
 *
 * @internal
 */
final class RouteFieldSkipTemplateDataMapperFormMetadataVisitor implements TypedFormMetadataVisitorInterface
{
    public function visitTypedFormMetadata(
        TypedFormMetadata $formMetadata,
        string $key,
        string $locale,
        array $metadataOptions = [],
    ): void {
        foreach ($formMetadata->getForms() as $form) {
            $this->visitFormMetadata($form);
        }
    }

    private function visitFormMetadata(FormMetadata $formMetadata): void
    {
        foreach ($formMetadata->getFlatFieldMetadata() as $field) {
            $type = $field->getType();
            if ('route' !== $type && 'page_tree_route' !== $type) {
                continue;
            }

            if ($field->hasTag(TemplateDataMapper::SKIP_TAG)) {
                continue;
            }

            $tag = new TagMetadata();
            $tag->setName(TemplateDataMapper::SKIP_TAG);
            $field->addTag($tag);
        }
    }
}
