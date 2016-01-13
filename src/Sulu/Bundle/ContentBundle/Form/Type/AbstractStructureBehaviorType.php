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
use Symfony\Component\Form\FormEvents;

/**
 * Forms extending this class handle documents which implement
 * the StructureBehavior.
 */
abstract class AbstractStructureBehaviorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('structureType', 'text');
        $builder->add('structure', 'text', ['property_path' => 'structure.stagedData']);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, ['Sulu\Component\Content\Compat\DataNormalizer', 'normalize']);
    }
}
