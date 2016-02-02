<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Sulu\Bundle\ContentBundle\Behat\BaseStructureContext;

/**
 * Behat context class for the SnippetBundle.
 */
class SnippetContext extends BaseStructureContext implements SnippetAcceptingContext
{
    /**
     * @Given there exists a snippet template ":name" with the following property configuration
     */
    public function thereExistsASnippetTemplateWithTheFollowingPropertyConfiguration($name, PyStringNode $string)
    {
        $template = <<<'EOT'
<?xml version="1.0" ?>

<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd"
          >

    <key>%s</key>

    <properties>
        <property name="title" type="text_line" mandatory="true">
            <meta>
                <title lang="de">Title</title>
                <title lang="en">Title</title>
            </meta>
        </property>

%s

    </properties>
</template>
EOT;

        $template = sprintf($template,
            $name, $string->getRaw()
        );

        $this->createStructureTemplate('snippet', $name, $template);
    }

    /**
     * @Given the following snippets exist:
     */
    public function theFollowingSnippetsExist(TableNode $table)
    {
        $data = $table->getColumnsHash();
        $this->createStructures('snippet', $data);
    }
}
