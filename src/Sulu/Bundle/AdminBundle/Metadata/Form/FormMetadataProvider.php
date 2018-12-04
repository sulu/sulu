<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\Form;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Field;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\FieldType;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Form;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Option;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Section;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
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
        array $locales,
        array $formDirectories,
        string $cacheDir,
        bool $debug
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->structureMetadataFactory = $structureMetadataFactory;
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

        return unserialize(file_get_contents($configCache->getPath()));
    }

    public function warmUp($cacheDir)
    {
        $formsMetadata = [];
        $formFinder = (new Finder())->in($this->formDirectories)->name('*.xml');
        foreach ($formFinder as $formFile) {
            $formMetadata = $this->formXmlLoader->load($formFile->getPathName());
            $formsMetadata[$formMetadata->getKey()] = $formMetadata;
        }

        foreach ($this->locales as $locale) {
            foreach ($formsMetadata as $key => $formMetadata) {
                $form = $this->mapFormMetadata($formMetadata, $locale);
                $configCache = $this->getConfigCache($key, $locale);
                $configCache->write(serialize($form), [new FileResource($formMetadata->getResource())]);
            }
        }
    }

    public function isOptional()
    {
        return false;
    }

    private function mapFormMetadata(FormMetadata $formMetadata, $locale)
    {
        $form = new Form();

        foreach ($formMetadata->getChildren() as $child) {
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

        return $form;
    }

    protected function mapSection(SectionMetadata $property, string $locale): Section
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

        foreach ($property->getComponents() as $component) {
            $fieldType = new FieldType($component->getName());
            $fieldType->setTitle($component->getTitle($locale) ?? ucfirst($component->getName()));

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

        if ('collection' === $parameter['type']) {
            foreach ($parameter['value'] as $parameterName => $parameterValue) {
                $valueOption = new Option();
                $valueOption->setName($parameterValue['name']);
                $valueOption->setValue($parameterValue['value']);

                $this->mapOptionMeta($parameterValue, $locale, $valueOption);

                $option->addValueOption($valueOption);
            }
        } elseif ('string' === $parameter['type']) {
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

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(sprintf('%s%s%s.%s', $this->cacheDir, DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}
