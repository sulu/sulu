<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader;

use Sulu\Component\Webspace\Loader\Exception\ExpectedDefaultTemplatesNotFound;
use Sulu\Component\Webspace\Webspace;

/**
 * This file loader is responsible for loading webspace configuration files using the xml format with the webspace
 * schema version 1.1.
 */
class XmlFileLoader11 extends XmlFileLoader10
{
    const SCHEMA_LOCATION = '/schema/webspace/webspace-1.1.xsd';

    const SCHEMA_URI = 'http://schemas.sulu.io/webspace/webspace-1.1.xsd';

    /**
     * {@inheritdoc}
     */
    protected function parseXml($file)
    {
        $webspace = parent::parseXml($file);

        $strategyNode = $this->xpath->query('/x:webspace/x:resource-locator/x:strategy')->item(0);
        if (null !== $strategyNode) {
            $webspace->setResourceLocatorStrategy($strategyNode->nodeValue);
        } else {
            $webspace->setResourceLocatorStrategy('tree_leaf_edit');
        }

        return $webspace;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateDefaultTemplates(Webspace $webspace)
    {
        $expected = ['page', 'home'];

        foreach ($this->xpath->query('/x:webspace/x:default-templates/x:default-template') as $node) {
            /* @var \DOMNode $node */
            $template = $node->nodeValue;
            $type = $node->attributes->getNamedItem('type')->nodeValue;

            $webspace->addDefaultTemplate($type, $template);
            if ($type === 'homepage') {
                $webspace->addDefaultTemplate('home', $template);
            }
        }

        $found = array_keys($webspace->getDefaultTemplates());
        foreach ($expected as $item) {
            if (!in_array($item, $found)) {
                throw new ExpectedDefaultTemplatesNotFound($this->webspace->getKey(), $expected, $found);
            }
        }

        return $webspace;
    }

    /**
     * Adds the template for the given types as described in the XML document.
     *
     * The types can be arbitrary, so that another bundle can easily add a new type and use the information from the
     * webspace.
     *
     * @param Webspace $webspace
     *
     * @return Webspace
     */
    protected function generateTemplates(Webspace $webspace)
    {
        foreach ($this->xpath->query('/x:webspace/x:templates/x:template') as $templateNode) {
            /* @var \DOMNode $templateNode */
            $template = $templateNode->nodeValue;
            $type = $templateNode->attributes->getNamedItem('type')->nodeValue;
            $webspace->addTemplate($type, $template);
        }

        return $webspace;
    }
}
