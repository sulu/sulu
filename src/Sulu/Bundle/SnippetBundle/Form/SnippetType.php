<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Form;

use Sulu\Bundle\PageBundle\Form\Type\AbstractStructureBehaviorType;
use Sulu\Bundle\PageBundle\Form\Type\UnstructuredType;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SnippetType extends AbstractStructureBehaviorType
{
    /**
     * @return void
     */
    public function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'data_class' => SnippetDocument::class,
        ]);

        parent::configureOptions($options);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('extensions', UnstructuredType::class, ['property_path' => 'extensionsData']);
        $builder->add('workflowStage');

        // TODO: Fix the admin interface to not send this junk (not required for snippets)
        $builder->add('redirectType', TextType::class, ['mapped' => false]);
        $builder->add('resourceSegment', TextType::class, ['mapped' => false]);
        $builder->add('navigationContexts', TextType::class, ['mapped' => false]);
        $builder->add('shadowLocaleEnabled', TextType::class, ['mapped' => false]);
    }
}
