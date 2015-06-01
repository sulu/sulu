<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Forms extending this class handle documents which implement
 * the StructureBehavior.
 */
abstract class AbstractStructureBehaviorType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults(array(
            'clear_missing_content' => false,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('structureType', 'text');
        $builder->add('content', 'text', array('property_path' => 'content.stagedData'));
        $builder->setAttribute('clear_missing_content', $options['clear_missing_content']);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array('Sulu\Component\Content\Compat\DataNormalizer', 'normalize'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitMapContent'));
    }

    public function postSubmitMapContent(FormEvent $event)
    {
        $document = $event->getData();
        $clearMissingContent = $event->getForm()->getConfig()->getAttribute('clear_missing_content');
        $document->getContent()->commitStagedData($clearMissingContent);
    }
}
