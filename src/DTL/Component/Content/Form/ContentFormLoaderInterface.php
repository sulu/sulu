<?php

namespace DTL\Component\Content\Form;

/**
 * Classes implementing this interface are responsible for retrieving content
 * (structure) form interfaces.
 *
 * For example, a loader might build a form based on an XML definition, or load
 * a cached form.
 */
interface ContentFormLoaderInterface
{
    /**
     * Load the named content form
     *
     * @param string
     *
     * @return ContentTypeInterface
     */
    public function load($formName);
}
