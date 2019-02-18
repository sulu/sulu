<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\ListMetadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ListMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        TranslatorInterface $translator
    ) {
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->translator = $translator;
    }

    public function getMetadata(string $key, string $locale)
    {
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors($key);

        if (!$fieldDescriptors) {
            throw new MetadataNotFoundException('list', $key);
        }

        $list = new ListMetadata();
        foreach ($fieldDescriptors as $fieldDescriptor) {
            $field = new Field($fieldDescriptor->getName());

            $field->setLabel($this->translator->trans($fieldDescriptor->getTranslation(), [], 'admin', $locale));
            $field->setType($fieldDescriptor->getType());
            $field->setVisibility($fieldDescriptor->getVisibility());
            $field->setSortable($fieldDescriptor->getSortable());

            $list->addField($field);
        }

        return $list;
    }
}
