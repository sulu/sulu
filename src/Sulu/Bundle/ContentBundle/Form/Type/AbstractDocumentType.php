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
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\DocumentManager\DocumentManager;

abstract class AbstractDocumentType extends AbstractType
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @param SessionManagerInterface $sessionManager
     * @param DocumentManager $documentManager
     */
    public function __construct(
        SessionManagerInterface $sessionManager,
        DocumentManager $documentManager
    )
    {
        $this->sessionManager = $sessionManager;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setRequired(array(
            'webspace_key',
            'structure_name',
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('parent', 'document_object');
        $builder->setAttribute('webspace_key', $options['webspace_key']);
        $builder->setAttribute('structure_name', $options['structure_name']);

        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitDocumentParent'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmitStructureName'));
    }

    /**
     * Set the document parent to be the webspace content path
     * when the document has no parent.
     *
     * @param FormEvent $event
     */
    public function postSubmitDocumentParent(FormEvent $event)
    {
        $document = $event->getData();

        if ($document->getParent()) {
            return;
        }

        $form = $event->getForm();
        $webspaceKey = $form->getConfig()->getAttribute('webspace_key');
        $parent = $this->documentManager->find($this->sessionManager->getContentPath($webspaceKey));

        if (null === $parent) {
            throw new \InvalidArgumentException(sprintf(
                'Could not determine parent for document with title "%s" in webspace "%s"',
                $document->getTitle(),
                $webspaceKey
            ));
        }

        $document->setParent($parent);
    }

    /**
     * Assign the name of the structure to the document
     *
     * @param FormEvent $event
     */
    public function postSubmitStructureName(FormEvent $event)
    {
        $document = $event->getData();
        $structureName = $event->getForm()->getConfig()->getAttribute('structure_name');
        $document->setStructureType($structureName);
    }
}
