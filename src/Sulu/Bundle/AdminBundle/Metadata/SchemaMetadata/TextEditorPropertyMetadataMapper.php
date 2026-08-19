<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;

class TextEditorPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();

        // text_editor values contain HTML markup, so configurable min/max-length constraints are not supported
        // (see docs/reference/content-types/text_editor.html). Only the mandatory (non-empty) constraint is mapped here.
        $textEditorMetadata = new StringMetadata(
            $mandatory ? 1 : null,
            null,
            null
        );

        if (!$mandatory) {
            $textEditorMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                new EmptyStringMetadata(),
                $textEditorMetadata,
            ]);
        }

        return new PropertyMetadata(
            (string) $propertyMetadata->getName(),
            $mandatory,
            $textEditorMetadata
        );
    }
}
