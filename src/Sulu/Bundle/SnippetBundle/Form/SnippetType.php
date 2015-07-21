<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Form;

use Sulu\Bundle\ContentBundle\Form\Type\AbstractStructureBehaviorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SnippetType extends AbstractStructureBehaviorType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        parent::setDefaultOptions($options);

        $options->setDefaults([
            'data_class' => 'Sulu\Bundle\SnippetBundle\Document\SnippetDocument',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('workflowStage');

        // TODO: Fix the admin interface to not send this junk (not required for snippets)
        $builder->add('redirectType', 'text', ['mapped' => false]);
        $builder->add('resourceSegment', 'text', ['mapped' => false]);
        $builder->add('navigationContexts', 'text', ['mapped' => false]);
        $builder->add('shadowLocaleEnabled', 'text', ['mapped' => false]);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'snippet';
    }
}
