<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Forms extending this class handle documents which implement
 * the StructureBehavior.
 */
abstract class AbstractStructureBehaviorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults([
            'clear_missing_content' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('structureType', 'text');
        $builder->add('structure', 'text', ['property_path' => 'structure.stagedData']);
        $builder->setAttribute('clear_missing_content', $options['clear_missing_content']);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, ['Sulu\Component\Content\Compat\DataNormalizer', 'normalize']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitMapContent']);
    }

    public function postSubmitMapContent(FormEvent $event)
    {
        $document = $event->getData();
        $clearMissingContent = $event->getForm()->getConfig()->getAttribute('clear_missing_content');
        $document->getStructure()->commitStagedData($clearMissingContent);
    }
}
