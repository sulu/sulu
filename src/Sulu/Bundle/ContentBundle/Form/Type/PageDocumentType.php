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

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Abstract test class for all content types
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class PageDocumentType extends AbstractDocumentType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults(array(
            'data_class' => 'Sulu\Bundle\ContentBundle\Document\PageDocument',
        ));

        parent::setDefaultOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('resourceSegment', 'text');
        $builder->add('navigationContexts', 'collection', array(
            'type' => 'text',
            'allow_add' => true,
            'allow_delete' => true,
        ));
        $builder->add('redirectType', 'text');
        $builder->add('redirectTarget', 'document_object');
        $builder->add('redirectExternal', 'text');
        $builder->add('workflowStage', 'integer');
        $builder->add('shadowLocaleEnabled', 'checkbox');
        $builder->add('shadowLocale', 'text'); // TODO: Should be choice of available shadow locales

        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'page';
    }
}
