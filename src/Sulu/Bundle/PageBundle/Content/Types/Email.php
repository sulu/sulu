<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content\Types;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\EmptyStringMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for Email.
 */
class Email extends SimpleContentType implements PropertyMetadataMapperInterface
{
    public function __construct()
    {
        parent::__construct('Email', '');
    }

    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $emailMetadata = new StringMetadata(
            null,
            null,
            null,
            'idn-email'
        );

        if (!$mandatory) {
            $emailMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                new EmptyStringMetadata(),
                $emailMetadata,
            ]);
        }

        return new PropertyMetadata(
            $fieldMetadata->getName(),
            $mandatory,
            $emailMetadata
        );
    }
}
