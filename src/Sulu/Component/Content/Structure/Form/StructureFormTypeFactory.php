<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Structure\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Form\ResolvedFormType;
use DTL\Component\Content\Structure\Factory\StructureFactory;
use Symfony\Component\Form\FormFactoryInterface;
use DTL\Component\Content\Document\DocumentInterface;
use DTL\Component\Content\PhpcrOdm\ContentContainer;

/**
 * Creates forms for structures using the Metadata from
 * StructureMetadataFactory.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class StructureFormTypeFactory
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var StructureFactory
     */
    private $structureFactory;

    /**
     * @param StructureFactory $structureFactory
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(StructureFactory $structureFactory, FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
        $this->structureFactory = $structureFactory;
    }

    /**
     * Create a structure form
     *
     * @param mixed $documenttype the document type (e.g. page, snippet)
     * @param mixed $structuretype the structure type (e.g. overview, example)
     * @param array $options form options (e.g. webspace, locale)
     */
    public function create($documentType, $structureType, array $options)
    {
        return $this->createBuilder($documentType, $structureType, $options)->getForm();
    }

    /**
     * Create a structure form builder
     *
     * @param mixed $documenttype the document type (e.g. page, snippet)
     * @param mixed $structuretype the structure type (e.g. overview, example)
     * @param array $options form options (e.g. webspace, locale)
     */
    public function createBuilder($documentType, $structureType, array $options)
    {
        $structure = $this->structureFactory->getStructure($documentType, $structureType, true);

        $builder = $this->formFactory->createNamedBuilder('content', 'form', null, array(
            'auto_initialize' => false, // auto initialize should only be for root nodes
            'data_class' => ContentContainer::class
        ));

        foreach ($structure->getChildren() as $name => $property) {
            if ($property->isMultiple()) {
                $builder->add($name, 'property_collection', array(
                    'type' => $property->type,
                    'options' => $property->parameters,
                    'label' => $property->title,
                    'min_occurs' => $property->minOccurs,
                    'max_occurs' => $property->maxOccurs,
                ));
            } else {
                $builder->add($name, $property->type, $property->parameters);
            }
        }

        return $builder;
    }

    /**
     * create the structure form type from a document
     *
     * REMOVE THIS PROBABLY IS NOT USED BY ANYTHING NOW
     *
     * @param DocumentInterface $document
     * @param array $options
     */
    public function createFor(DocumentInterface $document, array $options)
    {
        return $this->create($document->getDocumentType(), $document->getStructureType(), $options);
    }
}
