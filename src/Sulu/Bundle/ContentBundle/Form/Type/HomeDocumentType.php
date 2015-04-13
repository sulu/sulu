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

class HomeDocumentType extends BasePageDocumentType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults(array(
            'data_class' => 'Sulu\Bundle\ContentBundle\Document\HomeDocument',
        ));

        parent::setDefaultOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'home';
    }
}
