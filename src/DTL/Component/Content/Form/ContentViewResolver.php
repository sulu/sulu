<?php

namespace DTL\Component\Content\Form;

use DTL\Component\Content\Form\ContentView;

class ContentViewResolver
{
    public function resolve(StructureDocument $documnet)
    {
        $form = $this->contentFormBuilder->build($document->getFormType());

        $optionsResolver = new OptionsResolver();
        $contentView = new ContentView($form);
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
}
