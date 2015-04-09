<?php

namespace Sulu\Component\Content\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Sulu\Component\Content\Form\DataTransformer\DocumentToUuidTransformer;
use Sulu\Component\DocumentManager\DocumentManager;

/**
 * Form type which represents a Sulu Document
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
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'document_object';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefault('compound', false);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new DocumentToUuidTransformer($this->documentManager));
    }
}
