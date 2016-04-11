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

use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PageDocumentType extends BasePageDocumentType
{
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function __construct(
        SessionManagerInterface $sessionManager,
        DocumentManager $documentManager,
        MetadataFactory $metadataFactory
    ) {
        parent::__construct($sessionManager, $documentManager);

        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $metadata = $this->metadataFactory->getMetadataForAlias('page');

        $options->setDefaults([
            'data_class' => $metadata->getClass(),
        ]);

        parent::setDefaultOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'page';
    }
}
