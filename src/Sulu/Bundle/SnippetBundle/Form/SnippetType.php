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

        // TODO: Fix the admin interface to not send this junk (not required for snippets)
        $builder->add('redirectType', 'text', array('mapped' => false));
        $builder->add('navigationContexts', 'text', array('mapped' => false));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'snippet';
    }
}
