<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata as ExternalFormMetadata;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\Property;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class FormMetadataProvider implements MetadataProviderInterface, CacheWarmerInterface
{
    /**
     * @var FormXmlLoader
     */
    private $formXmlLoader;

    /**
     * @var StructureMetadataFactory
     */
    private $structureMetadataFactory;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @var string[]
     */
    private $formDirectories;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(
        FormXmlLoader $formXmlLoader,
        StructureMetadataFactory $structureMetadataFactory,
        ExpressionLanguage $expressionLanguage,
        array $locales,
        array $formDirectories,
        string $cacheDir,
        bool $debug
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->expressionLanguage = $expressionLanguage;
        $this->locales = $locales;
        $this->formDirectories = $formDirectories;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function getMetadata(string $key, string $locale)
    {
        $configCache = $this->getConfigCache($key, $locale);

        if (!$configCache->isFresh()) {
            $this->warmUp($this->cacheDir);
        }

        if (!file_exists($configCache->getPath())) {
            throw new MetadataNotFoundException('form', $key);
        }

        $form = unserialize(file_get_contents($configCache->getPath()));

        if ($form instanceof FormMetadata) {
            $this->evaluateFormItemExpressions($form->getItems());
        } elseif ($form instanceof TypedForm) {
            foreach ($form->getForms() as $formType) {
                $this->evaluateFormItemExpressions($formType->getItems());
            }
        }

        return $form;
    }

    /**
     * @param Item[] $items
     */
    private function evaluateFormItemExpressions(array $items)
    {
        foreach ($items as $item) {
            if ($item instanceof Section) {
                $this->evaluateFormItemExpressions($item->getItems());
            }

            if ($item instanceof Field) {
                foreach ($item->getTypes() as $type) {
                    $this->evaluateFormItemExpressions($type->getItems());
                }

                foreach ($item->getOptions() as $option) {
                    if (Option::TYPE_EXPRESSION === $option->getType()) {
                        $option->setValue($this->expressionLanguage->evaluate($option->getValue()));
                    }
                }
            }
        }
    }

    public function warmUp($cacheDir)
    {
        $formsMetadata = [];
        $formFinder = (new Finder())->in($this->formDirectories)->name('*.xml');
        foreach ($formFinder as $formFile) {
            $formMetadata = $this->formXmlLoader->load($formFile->getPathName());
            $formKey = $formMetadata->getKey();
            if (!array_key_exists($formKey, $formsMetadata)) {
                $formsMetadata[$formKey] = [];
            }
            $formsMetadata[$formKey][] = $formMetadata;
        }

        $structuresMetadataByTypes = [];
        foreach ($this->structureMetadataFactory->getStructureTypes() as $structureType) {
            foreach ($this->structureMetadataFactory->getStructures($structureType) as $structureMetadata) {
                if ($structureMetadata->isInternal() || 'home' === $structureType) {
                    continue;
                }

                $structuresMetadataByTypes[$structureType][] = $structureMetadata;
            }
        }

        foreach ($this->locales as $locale) {
            foreach ($structuresMetadataByTypes as $structureType => $structuresMetadata) {
                $form = $this->mapStructureMetadata($structuresMetadata, $structureType, $locale);
                $configCache = $this->getConfigCache($structureType, $locale);
                $configCache->write(
                    serialize($form),
                    array_map(function(StructureMetadata $structureMetadata) {
                        return new FileResource($structureMetadata->getResource());
                    }, $structuresMetadata)
                );
            }

            foreach ($formsMetadata as $key => $formMetadata) {
                $form = $this->mapFormsMetadata($formMetadata, $locale);
                $configCache = $this->getConfigCache($key, $locale);
                $configCache->write(
                    serialize($form),
                    array_map(function(ExternalFormMetadata $formMetadata) {
                        return new FileResource($formMetadata->getResource());
                    }, $formMetadata)
                );
            }
        }
    }

    public function isOptional()
    {
        return false;
    }

    /**
     * @param StructureMetadata[] $structuresMetadata
     */
    public function mapStructureMetadata(array $structuresMetadata, string $structureType, string $locale)
    {
        $typedForm = new TypedForm();

        foreach ($structuresMetadata as $structureMetadata) {
            $form = new FormMetadata();
            $form->setName($structureMetadata->getName());
            $form->setTitle($structureMetadata->getTitle($locale) ?? ucfirst($structureMetadata->getName()));
            $this->mapChildren($structureMetadata->getChildren(), $form, $locale);
            $form->setSchema($this->mapSchema($structureMetadata->getProperties()));

            $typedForm->addForm($structureMetadata->getName(), $form);
        }

        return $typedForm;
    }

    /**
     * @param ExternalFormMetadata[] $formsMetadata
     */
    private function mapFormsMetadata(array $formsMetadata, $locale)
    {
        $mergedForm = null;
        foreach ($formsMetadata as $formMetadata) {
            $form = new FormMetadata();
            $this->mapChildren($formMetadata->getChildren(), $form, $locale);

            $schema = $this->mapSchema($formMetadata->getProperties());
            $formSchema = $formMetadata->getSchema();
            if ($formSchema) {
                $schema = $schema->merge($formSchema);
            }

            $form->setSchema($schema);

            if (!$mergedForm) {
                $mergedForm = $form;
            } else {
                $mergedForm = $mergedForm->merge($form);
            }
        }

        return $mergedForm;
    }

    /**
     * @param ItemMetadata[] $children
     */
    private function mapChildren(array $children, FormMetadata $form, string $locale)
    {
        foreach ($children as $child) {
            if ($child instanceof BlockMetadata) {
                $item = $this->mapBlock($child, $locale);
            } elseif ($child instanceof PropertyMetadata) {
                $item = $this->mapProperty($child, $locale);
            } elseif ($child instanceof SectionMetadata) {
                $item = $this->mapSection($child, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . get_class($child) . '"');
            }

            $form->addItem($item);
        }
    }

    private function mapSection(SectionMetadata $property, string $locale): Section
    {
        $section = new Section($property->getName());

        $title = $property->getTitle($locale);
        if ($title) {
            $section->setLabel($title);
        }

        $section->setSize($property->getSize());
        $section->setDisabledCondition($property->getDisabledCondition());
        $section->setVisibleCondition($property->getVisibleCondition());

        foreach ($property->getChildren() as $component) {
            if ($component instanceof BlockMetadata) {
                $item = $this->mapBlock($component, $locale);
            } elseif ($component instanceof PropertyMetadata) {
                $item = $this->mapProperty($component, $locale);
            } elseif ($component instanceof SectionMetadata) {
                $item = $this->mapSection($component, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . get_class($property) . '"');
            }

            $section->addItem($item);
        }

        return $section;
    }

    private function mapBlock(BlockMetadata $property, string $locale): Field
    {
        $field = $this->mapProperty($property, $locale);
        $field->setDefaultType($property->getDefaultComponentName());

        foreach ($property->getComponents() as $component) {
            $blockType = new FormMetadata();
            $blockType->setName($component->getName());
            $blockType->setTitle($component->getTitle($locale) ?? ucfirst($component->getName()));

            foreach ($component->getChildren() as $componentProperty) {
                if ($componentProperty instanceof PropertyMetadata) {
                    $blockTypeField = $this->mapProperty($componentProperty, $locale);
                    $blockType->addItem($blockTypeField);
                }
            }

            $field->addType($blockType);
        }

        return $field;
    }

    private function mapProperty(PropertyMetadata $property, string $locale): Field
    {
        $field = new Field($property->getName());
        foreach ($property->getTags() as $tag) {
            $fieldTag = new Tag();
            $fieldTag->setName($tag['name']);
            $fieldTag->setPriority($tag['priority']);
            $field->addTag($fieldTag);
        }

        $field->setLabel($property->getTitle($locale));
        $field->setDisabledCondition($property->getDisabledCondition());
        $field->setVisibleCondition($property->getVisibleCondition());
        $field->setDescription($property->getDescription($locale));
        $field->setType($property->getType());
        $field->setSize($property->getSize());
        $field->setRequired($property->isRequired());
        $field->setSpaceAfter($property->getSpaceAfter());

        foreach ($property->getParameters() as $parameter) {
            $field->addOption($this->mapOption($parameter, $locale));
        }

        return $field;
    }

    private function mapOption($parameter, string $locale): Option
    {
        $option = new Option();
        $option->setName($parameter['name']);
        $option->setType($parameter['type']);

        if ('collection' === $parameter['type']) {
            foreach ($parameter['value'] as $parameterName => $parameterValue) {
                $valueOption = new Option();
                $valueOption->setName($parameterValue['name']);
                $valueOption->setValue($parameterValue['value']);

                $this->mapOptionMeta($parameterValue, $locale, $valueOption);

                $option->addValueOption($valueOption);
            }
        } elseif ('string' === $parameter['type'] || 'expression' === $parameter['type']) {
            $option->setValue($parameter['value']);
            $this->mapOptionMeta($parameter, $locale, $option);
        } else {
            throw new \Exception('Unsupported parameter given "' . get_class($parameter) . '"');
        }

        return $option;
    }

    private function mapOptionMeta($parameterValue, string $locale, Option $option)
    {
        if (!array_key_exists('meta', $parameterValue)) {
            return;
        }

        foreach ($parameterValue['meta'] as $metaKey => $metaValues) {
            if (array_key_exists($locale, $metaValues)) {
                switch ($metaKey) {
                    case 'title':
                        $option->setTitle($metaValues[$locale]);
                        break;
                    case 'info_text':
                        $option->setInfotext($metaValues[$locale]);
                        break;
                    case 'placeholder':
                        $option->setPlaceholder($metaValues[$locale]);
                        break;
                }
            }
        }
    }

    /**
     * @param ItemMetadata[] $itemsMetadata
     */
    private function mapSchema(array $itemsMetadata): SchemaMetadata
    {
        return new SchemaMetadata($this->mapSchemaProperties($itemsMetadata));
    }

    /**
     * @param ItemMetadata[] $itemsMetadata
     */
    private function mapSchemaProperties(array $itemsMetadata)
    {
        return array_filter(array_map(function(ItemMetadata $itemMetadata) {
            if ($itemMetadata instanceof SectionMetadata) {
                return $this->mapSchemaProperties($itemMetadata->getChildren());
            }

            if (!$itemMetadata->isRequired()) {
                return;
            }

            return new Property($itemMetadata->getName(), $itemMetadata->isRequired());
        }, $itemsMetadata));
    }

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(sprintf('%s%s%s.%s', $this->cacheDir, DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}
