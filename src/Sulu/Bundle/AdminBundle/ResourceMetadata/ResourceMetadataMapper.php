<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata;

use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Datagrid;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Field as DatagridField;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ResourceMetadataMapper
{
    /**
     * @var FieldDescriptorFactoryInterface
     */
    protected $fieldDescriptorFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        TranslatorInterface $translator
    ) {
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->translator = $translator;
    }

    public function mapDatagrid(?string $class, string $locale): ?Datagrid
    {
        if (!$class) {
            return null;
        }

        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptorForClass($class);

        $datagrid = new Datagrid();
        foreach ($fieldDescriptors as $fieldDescriptor) {
            $field = new DatagridField($fieldDescriptor->getName());

            $field->setLabel($this->translator->trans($fieldDescriptor->getTranslation(), [], 'admin'));
            $field->setType($fieldDescriptor->getType());
            $field->setVisibility($fieldDescriptor->getVisibility());
            $field->setSortable($fieldDescriptor->getSortable());

            $datagrid->addField($field);
        }

        return $datagrid;
    }
}
