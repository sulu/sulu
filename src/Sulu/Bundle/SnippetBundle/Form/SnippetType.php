<?php

namespace Sulu\Bundle\SnippetBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SnippetType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults(array(
            'data_class' => 'Sulu\Bundle\SnippetBundle\Document\SnippetDocument',
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('workflowStage');
        $builder->add('structureType', 'text');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'snippet';
    }
}
