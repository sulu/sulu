<?php

namespace DTL\Component\Content\Form;

use DTL\Component\Content\Form\ContentView;
use DTL\Bundle\ContentBundle\Document\StructureDocument;
use DTL\Component\Content\Form\ContentFormLoaderInterface;
use DTL\Component\Content\Form\ContentTypeInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Class responsible for resolving structure documents to view objects
 */
class ContentViewResolver
{
    private $loader;

    /**
     * @param ContentFormLoaderInterface $loader
     */
    public function __construct(ContentFormLoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Resolve the given structure document into a content view
     *
     * @param StructureDocument $documnet
     */
    public function resolve(StructureDocument $document)
    {
        $form = $this->loader->load($document->getFormType());
        $form->setData($document->getContentData());

        $contentView = new ContentView();

        foreach ($form as $childName => $childForm) {
            $childContentView = new ContentView();
            $contentView[$childName] = $this->buildContentView($childContentView, $childForm);
        }

        return $contentView;
    }

    /**
     * Create a view iterator for the given content documents
     *
     * @param StructureDocument[] $documents
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
        if ($formType instanceof ContentTypeInterface) {
            $formType->buildContentView($contentView, $form);
            return;
        }

        // otherwise use the value if the form element directly
        // for example, if this is an email form type, then the value
        // will be a string containing the email
        $contentView->setValue($form->getData());
    }
}
