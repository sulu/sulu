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

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class HomeDocumentType extends BasePageDocumentType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults([
            'data_class' => 'Sulu\Bundle\ContentBundle\Document\HomeDocument',
        ]);

        parent::setDefaultOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'home';
    }
}
