<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

class HomeDocumentType extends BasePageDocumentType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'data_class' => 'Sulu\Bundle\ContentBundle\Document\HomeDocument',
        ]);

        parent::configureOptions($options);
    }
}
