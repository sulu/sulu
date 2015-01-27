<?php

namespace DTL\Component\Content\Form;

use DTL\Component\Content\Form\ContentView;
use DTL\Bundle\ContentBundle\Document\FormDocument;
use DTL\Component\Content\Form\ContentTypeInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormInterface;
use DTL\Component\Content\Form\ContentResolvedTypeInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Class responsible for resolving structure documents to view objects
 */
class ContentViewResolver
{
    private $factory;

    /**
     * @param ContentFormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Resolve the given structure document into a content view
     *
     * @param FormDocument $documnet
     */
    public function resolve(FormDocument $document)
    {
        $formType = $document->getFormType();

        if (!$formType) {
            throw new \RuntimeException(sprintf(
                'Form document at path "%s" does not have an associated form type',
                $document->getPath()
            ));
        }

        $form = $this->factory->create($formType);
        $form->setData($document->getContentData());

        $contentView = new ContentView();

        $children = array();
        foreach ($form->all() as $childName => $childForm) {
            $childContentView = new ContentView();
            $this->buildContentView($childContentView, $childForm);
            $children[$childName] = $childContentView;
        }

        $contentView->setChildren($children);

        return $contentView;
    }

    /**
     * Create a view iterator for the given content documents
     *
     * @param FormDocument[] $documents
     */
    public function createIterator($documents)
    {
        return new ContentViewIterator($this, $documents);
    }

    /**
     * @param FormTypeInterface $childForm
     * @param ContentView $childContentView
     */
    private function buildContentView(ContentView $contentView, FormInterface $form)
    {
        $formType = $form->getConfig()->getType();

        // if this is a Sulu content type then use it to build
        // the its own content view
        if ($formType instanceof ContentResolvedTypeInterface) {
            $formType->buildContentView($contentView, $form);
            return;
        }

        // otherwise use the value if the form element directly
        // for example, if this is an email form type, then the value
        // will be a string containing the email
        $contentView->setValue($form->getData());
    }
}
