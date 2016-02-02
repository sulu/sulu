<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Form\Type;

use Sulu\Component\Content\Form\DataTransformer\DocumentToUuidTransformer;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form type which represents a Sulu Document.
 */
class DocumentObjectType extends AbstractType
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'document_object';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefault('compound', false);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new DocumentToUuidTransformer($this->documentManager));
    }
}
