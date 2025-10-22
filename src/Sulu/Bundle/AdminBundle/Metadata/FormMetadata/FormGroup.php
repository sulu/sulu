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

final readonly class FormGroup
{
    /**
     * @param string[] $templates
     */
    public function __construct(
        public string $identifier,
        public string $title,
        public array $templates = [],
    ) {
    }

    public function withTemplate(string $template): self
    {
        return new self(
            $this->identifier,
            $this->title,
            [...$this->templates, $template],
        );
    }
}
