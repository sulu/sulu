<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

/**
 * Container for a default template definition.
 */
class DefaultTemplate
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $template;

    /**
     * @var string|null
     */
    private $parentTemplate;

    public function __construct(string $type, string $template, ?string $parentTemplate = null)
    {
        $this->type = $type;
        $this->template = $template;
        $this->parentTemplate = $parentTemplate;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return string|null
     */
    public function getParentTemplate(): ?string
    {
        return $this->parentTemplate;
    }
}
