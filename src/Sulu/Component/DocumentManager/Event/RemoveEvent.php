<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Symfony\Component\OptionsResolver\OptionsResolver;

class RemoveEvent extends AbstractDocumentEvent
{
    use EventOptionsTrait;

    /**
     * @param object $document
     * @param mixed[] $options
     */
    public function __construct($document, array $options = [])
    {
        parent::__construct($document);

        /** @var OptionsResolver $optionsResovler */
        $optionsResovler = $options;

        $this->options = $optionsResovler;
    }
}
