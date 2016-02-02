<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Form;

use Sulu\Bundle\ContentBundle\Form\Type\AbstractStructureBehaviorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SnippetType extends AbstractStructureBehaviorType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        parent::setDefaultOptions($options);

        $options->setDefaults([
            'data_class' => 'Sulu\Bundle\SnippetBundle\Document\SnippetDocument',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('workflowStage');

        // TODO: Fix the admin interface to not send this junk (not required for snippets)
        $builder->add('redirectType', TextType::class, ['mapped' => false]);
        $builder->add('resourceSegment', TextType::class, ['mapped' => false]);
        $builder->add('navigationContexts', TextType::class, ['mapped' => false]);
        $builder->add('shadowLocaleEnabled', TextType::class, ['mapped' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'snippet';
    }
}
