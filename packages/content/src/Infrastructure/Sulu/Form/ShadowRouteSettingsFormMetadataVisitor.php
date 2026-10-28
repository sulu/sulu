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

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The content tab is hidden while a shadow is enabled, but the shadow keeps its own url.
 * This visitor adds a field mirroring the route field of the template to the shadow section
 * of the settings form, for content with a route only (it passes the `templateType` option).
 *
 * @internal
 */
class ShadowRouteSettingsFormMetadataVisitor implements FormMetadataVisitorInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('content_settings' !== $formMetadata->getKey() || !isset($metadataOptions['templateType'])) {
            return;
        }

        $shadowSection = $formMetadata->getItems()['shadow'] ?? null;

        if (!$shadowSection instanceof SectionMetadata) {
            return;
        }

        $field = new FieldMetadata('url');
        $field->setType('shadow_route');
        $field->setLabel($this->translator->trans('sulu_content.shadow_route', [], 'admin', $locale), $locale);
        $field->setVisibleCondition('shadowOn == true');

        $shadowSection->addItem($field);
    }
}
