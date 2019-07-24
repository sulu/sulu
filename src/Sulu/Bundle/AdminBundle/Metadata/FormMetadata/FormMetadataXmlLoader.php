<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Symfony\Component\Finder\Finder;

class FormMetadataXmlLoader implements FormMetadataLoaderInterface
{
    private $formXmlLoader;

    private $formDirectories;

    public function __construct(
        FormXmlLoader $formXmlLoader,
        array $formDirectories
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->formDirectories = $formDirectories;
    }

    public function load()
    {
        $formsMetadata = [];
        $formFinder = (new Finder())->in($this->formDirectories)->name('*.xml');
        foreach ($formFinder as $formFile) {
            $formMetadata = $this->formXmlLoader->load($formFile->getPathName());
            $formKey = $formMetadata->getKey();
            if (!array_key_exists($formKey, $formsMetadata)) {
                $formsMetadata[$formKey] = [];
            }
            $formsMetadata[$formKey][] = $formMetadata;
        }

        return $formsMetadata;
    }
}
