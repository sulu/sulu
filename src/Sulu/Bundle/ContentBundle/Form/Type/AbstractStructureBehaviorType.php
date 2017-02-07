<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Form\Type;

use Sulu\Component\Content\Compat\DataNormalizer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        $builder->add('title', TextType::class);
        $builder->add('structureType', TextType::class);
        $builder->add('structure', TextType::class, ['property_path' => 'structure.stagedData']);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [DataNormalizer::class, 'normalize']);
    }
}
