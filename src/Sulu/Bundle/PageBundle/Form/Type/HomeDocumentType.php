<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Form\Type;

use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HomeDocumentType extends BasePageDocumentType
{
    public function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'data_class' => HomeDocument::class,
        ]);

        parent::configureOptions($options);
    }
}
