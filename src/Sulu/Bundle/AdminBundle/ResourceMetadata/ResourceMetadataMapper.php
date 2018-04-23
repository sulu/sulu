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
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Field as FormField;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\FieldType;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Form;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Option;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Section;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;
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

    public function mapDatagrid(string $class, string $locale): Datagrid
    {
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptorForClass($class);

        $datagrid = new Datagrid();
        foreach ($fieldDescriptors as $fieldDescriptor) {
            $field = new DatagridField($fieldDescriptor->getName());

            $field->setLabel($this->translator->trans($fieldDescriptor->getTranslation(), [], 'admin'));
            $field->setType($fieldDescriptor->getType());
            $field->setVisibility($fieldDescriptor->getVisibility());

            $datagrid->addField($field);
        }

        return $datagrid;
    }

    /**
     * @param PropertyMetadata $properties
     */
    public function mapForm(array $properties, string $locale): Form
    {
        $form = new Form();

        /** @var PropertyMetadata $property */
        foreach ($properties as $property) {
            if ($property instanceof BlockMetadata) {
                $item = $this->mapBlock($property, $locale);
            } elseif ($property instanceof PropertyMetadata) {
                $item = $this->mapProperty($property, $locale);
            } elseif ($property instanceof SectionMetadata) {
                $item = $this->mapSection($property, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . get_class($property) . '"');
            }

            $form->addItem($item);
        }

        return $form;
    }

    protected function mapSection(SectionMetadata $property, string $locale): Section
    {
        $section = new Section($property->getName());

        $section->setLabel($property->getTitle($locale));
        $section->setSize($property->getSize());

        foreach ($property->getChildren() as $component) {
            if ($component instanceof BlockMetadata) {
                $item = $this->mapBlock($component, $locale);
            } elseif ($component instanceof PropertyMetadata) {
                $item = $this->mapProperty($component, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . get_class($property) . '"');
            }

            $section->addItem($item);
        }

        return $section;
    }

    protected function mapProperty(PropertyMetadata $property, string $locale): FormField
    {
        $field = new FormField($property->getName());

        $field->setLabel($property->getTitle($locale));
        $field->setType($property->getType());
        $field->setSize($property->getSize());
        $field->setRequired($property->isRequired());
        $field->setSpaceAfter($property->getSpaceAfter());

        foreach ($property->getParameters() as $parameter) {
            $option = new Option();
            $option->setName($parameter['name']);

            if ('collection' === $parameter['type']) {
                foreach ($parameter['value'] as $parameterName => $parameterValue) {
                    $valueOption = new Option();
                    $valueOption->setName($parameterName);
                    $valueOption->setValue($parameterValue['value']);

                    if (array_key_exists('meta', $parameterValue)) {
                        foreach ($parameterValue['meta'] as $metaKey => $metaValues) {
                            if (array_key_exists($locale, $metaValues)) {
                                switch ($metaKey) {
                                    case 'title':
                                        $valueOption->setTitle($metaValues[$locale]);
                                        break;
                                    case 'placeholder':
                                        $valueOption->setPlaceholder($metaValues[$locale]);
                                        break;
                                }
                            }
                        }
                    }

                    $option->addValueOption($valueOption);
                }
            } elseif ('string' === $parameter['type']) {
                $option->setValue($parameter['value']);
            } else {
                throw new \Exception('Unsupported parameter given "' . get_class($parameter) . '"');
            }

            $field->addOption($option);
        }

        return $field;
    }

    protected function mapBlock(BlockMetadata $property, string $locale): FormField
    {
        $field = $this->mapProperty($property, $locale);

        foreach ($property->getComponents() as $component) {
            $fieldType = new FieldType($component->getName());
            $fieldType->setTitle($component->getTitle($locale));

            $componentForm = new Form();

            foreach ($component->getChildren() as $componentProperty) {
                if ($componentProperty instanceof PropertyMetadata) {
                    $componentField = $this->mapProperty($componentProperty, $locale);
                    $componentForm->addItem($componentField);
                }
            }

            $fieldType->setForm($componentForm);
            $field->addType($fieldType);
        }

        return $field;
    }

    /**
     * @param PropertyMetadata[] $properties
     */
    public function mapSchema(array $properties): Schema
    {
        $schema = new Schema();

        foreach ($properties as $property) {
            if ($property->isRequired()) {
                $schema->addRequired($property->getName());
            }
        }

        return $schema;
    }
}
